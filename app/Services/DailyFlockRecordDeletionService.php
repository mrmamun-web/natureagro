<?php

namespace App\Services;

use App\Exceptions\RecordHasDependencies;
use App\Models\EggInventoryMovement;
use App\Models\EggStock;
use App\Models\FlockDailyRecord;
use App\Models\IncubationBatch;
use App\Models\InventoryCostLayer;
use App\Models\InventoryCostMovement;
use App\Models\ParentFlock;
use App\Models\ParentFlockMovement;
use Illuminate\Database\Eloquent\Collection;

class DailyFlockRecordDeletionService
{
    public function deleteWithinTransaction(FlockDailyRecord $record): void
    {
        $flock = ParentFlock::query()->lockForUpdate()->findOrFail($record->parent_flock_id);
        $sourceType = $record->getMorphClass();
        $sourceTypes = [$sourceType, 'legacy_flock_daily_record'];

        $eggMovements = EggInventoryMovement::query()
            ->whereIn('source_type', $sourceTypes)
            ->where('source_id', $record->id)
            ->lockForUpdate()
            ->get();
        $costMovements = InventoryCostMovement::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $record->id)
            ->lockForUpdate()
            ->get();
        $sourceLayers = InventoryCostLayer::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $record->id)
            ->lockForUpdate()
            ->get();
        $flockMovements = ParentFlockMovement::query()
            ->where('flock_daily_record_id', $record->id)
            ->lockForUpdate()
            ->get();

        $this->ensureEggMovementsMatchRecord($record, $eggMovements);
        $this->ensureFlockMovementsMatchRecord($record, $flockMovements);
        $this->ensureCostMovementsMatchRecord($record, $costMovements);
        $this->deleteLaterUnhatchedIncubations($record, $eggMovements);
        $this->ensureNoLaterEggMovements($flock->id, $eggMovements);
        $this->ensureNoLaterCostMovements($record, $costMovements);
        $this->ensureSourceLayersAreUnused($sourceType, $record->id, $sourceLayers);

        $goodDelta = (int) $eggMovements->sum('good_delta');
        $damagedDelta = (int) $eggMovements->sum('damaged_delta');

        if ($goodDelta !== 0 || $damagedDelta !== 0) {
            $stock = EggStock::query()
                ->where('parent_flock_id', $flock->id)
                ->lockForUpdate()
                ->first();

            if ($stock === null
                || $stock->good_available < $goodDelta
                || $stock->damaged < $damagedDelta) {
                throw new RecordHasDependencies(
                    'The collected eggs have already been used or the current egg stock no longer matches this record. Nothing was removed.',
                );
            }

            $stock->good_available -= $goodDelta;
            $stock->damaged -= $damagedDelta;
            $stock->save();
        }

        $flock->current_male_quantity += (int) $record->male_mortality;
        $flock->current_female_quantity += (int) $record->female_mortality;

        if (! FlockDailyRecord::query()
            ->where('parent_flock_id', $flock->id)
            ->where('recorded_on', '>', $record->recorded_on)
            ->exists()) {
            $flock->health_status = FlockDailyRecord::query()
                ->where('parent_flock_id', $flock->id)
                ->where('id', '!=', $record->id)
                ->orderByDesc('recorded_on')
                ->value('health_status') ?? ParentFlock::HEALTHY;
        }

        $flock->save();

        $affectedLayerIds = $costMovements
            ->pluck('inventory_cost_layer_id')
            ->filter()
            ->unique()
            ->values();
        $sourceLayerIds = $sourceLayers->modelKeys();

        InventoryCostMovement::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $record->id)
            ->delete();

        if ($sourceLayerIds !== []) {
            InventoryCostLayer::query()->whereKey($sourceLayerIds)->delete();
            $affectedLayerIds = $affectedLayerIds->diff($sourceLayerIds);
        }

        app(InventoryCostLayerRecalculator::class)->recalculate($affectedLayerIds);

        EggInventoryMovement::query()
            ->whereIn('source_type', $sourceTypes)
            ->where('source_id', $record->id)
            ->delete();
        $flockMovements->each->delete();

        if (! $record->delete()) {
            throw new RecordHasDependencies('Deletion was blocked by the application. Nothing was removed.');
        }
    }

    /**
     * @param  Collection<int, EggInventoryMovement>  $movements
     */
    private function ensureEggMovementsMatchRecord(FlockDailyRecord $record, Collection $movements): void
    {
        $goodDelta = (int) $movements->sum('good_delta');
        $damagedDelta = (int) $movements->sum('damaged_delta');

        if ($goodDelta !== (int) $record->good_eggs || $damagedDelta !== (int) $record->damaged_eggs) {
            throw new RecordHasDependencies(
                'The egg stock history does not match this daily record. Nothing was removed.',
            );
        }

        if ($movements->isEmpty() && ((int) $record->good_eggs > 0 || (int) $record->damaged_eggs > 0)) {
            throw new RecordHasDependencies(
                'The egg stock history for this daily record is missing. Nothing was removed.',
            );
        }
    }

    /**
     * @param  Collection<int, ParentFlockMovement>  $movements
     */
    private function ensureFlockMovementsMatchRecord(FlockDailyRecord $record, Collection $movements): void
    {
        $male = (int) $movements->sum('male_quantity');
        $female = (int) $movements->sum('female_quantity');

        if ($male !== (int) $record->male_mortality || $female !== (int) $record->female_mortality) {
            throw new RecordHasDependencies(
                'The flock stock history does not match this daily record. Nothing was removed.',
            );
        }
    }

    /**
     * @param  Collection<int, InventoryCostMovement>  $movements
     */
    private function ensureCostMovementsMatchRecord(FlockDailyRecord $record, Collection $movements): void
    {
        $eggQuantity = (float) $movements
            ->where('inventory_type', 'egg_stock')
            ->sum('quantity_delta');
        $maleMortality = (float) $movements
            ->where('inventory_type', 'parent_flock')
            ->where('gender', 'male')
            ->where('movement_type', 'mortality')
            ->sum('quantity_delta');
        $femaleMortality = (float) $movements
            ->where('inventory_type', 'parent_flock')
            ->where('gender', 'female')
            ->where('movement_type', 'mortality')
            ->sum('quantity_delta');

        if (abs($eggQuantity - (int) $record->good_eggs) > 0.0001
            || abs($maleMortality + (int) $record->male_mortality) > 0.0001
            || abs($femaleMortality + (int) $record->female_mortality) > 0.0001) {
            throw new RecordHasDependencies(
                'The cost history does not match the egg or mortality totals in this daily record. Nothing was removed.',
            );
        }
    }

    /**
     * @param  Collection<int, EggInventoryMovement>  $movements
     */
    private function ensureNoLaterEggMovements(int $flockId, Collection $movements): void
    {
        if ($movements->isEmpty()) {
            return;
        }

        $lastSourceMovementId = (int) $movements->max('id');

        if (EggInventoryMovement::query()
            ->where('parent_flock_id', $flockId)
            ->where('id', '>', $lastSourceMovementId)
            ->exists()) {
            throw new RecordHasDependencies(
                'Later egg stock movements depend on this collection. Remove or correct those later records first.',
            );
        }
    }

    /**
     * @param  Collection<int, EggInventoryMovement>  $movements
     */
    private function deleteLaterUnhatchedIncubations(FlockDailyRecord $record, Collection $movements): void
    {
        if ($movements->isEmpty()) {
            return;
        }

        $lastSourceMovementId = (int) $movements->max('id');
        $laterMovements = EggInventoryMovement::query()
            ->where('parent_flock_id', $record->parent_flock_id)
            ->where('id', '>', $lastSourceMovementId)
            ->orderByDesc('id')
            ->get();

        if ($laterMovements->isEmpty()) {
            return;
        }

        if (FlockDailyRecord::query()
            ->where('parent_flock_id', $record->parent_flock_id)
            ->where('recorded_on', '<', $record->recorded_on)
            ->where('good_eggs', '>', 0)
            ->exists()) {
            throw new RecordHasDependencies(
                'Eggs from multiple daily records are mixed in stock. Delete the incubation batch separately before removing this record.',
            );
        }

        foreach ($laterMovements as $movement) {
            if ($movement->movement_type !== EggInventoryMovement::TYPE_INCUBATION_RESERVE
                || $movement->source_type !== IncubationBatch::class) {
                throw new RecordHasDependencies(
                    'Later egg stock activity is not an unused incubation reservation. Remove or correct those later records first.',
                );
            }
        }

        $batchIds = $laterMovements->pluck('source_id')->unique();

        foreach ($batchIds as $batchId) {
            $batch = IncubationBatch::query()
                ->whereKey($batchId)
                ->where('parent_flock_id', $record->parent_flock_id)
                ->lockForUpdate()
                ->first();

            if ($batch === null || $batch->status !== IncubationBatch::STATUS_INCUBATING) {
                throw new RecordHasDependencies(
                    'A later incubation has already progressed. Remove or correct that batch first.',
                );
            }

            app(IncubationBatchDeletionService::class)->deleteWithinTransaction($batch);
        }
    }

    /**
     * @param  Collection<int, InventoryCostMovement>  $movements
     */
    private function ensureNoLaterCostMovements(FlockDailyRecord $record, Collection $movements): void
    {
        foreach ($movements->groupBy(fn (InventoryCostMovement $movement): string => implode('|', [
            $movement->inventory_type,
            $movement->inventory_id,
            $movement->gender ?? '',
        ])) as $group) {
            /** @var InventoryCostMovement $lastSourceMovement */
            $lastSourceMovement = $group->sortByDesc('id')->first();

            if (InventoryCostMovement::query()
                ->where('inventory_type', $lastSourceMovement->inventory_type)
                ->where('inventory_id', $lastSourceMovement->inventory_id)
                ->where('gender', $lastSourceMovement->gender)
                ->where('id', '>', $lastSourceMovement->id)
                ->exists()) {
                throw new RecordHasDependencies(
                    'Later cost movements depend on this daily record. Remove or correct those later records first.',
                );
            }
        }

        if ((int) $record->good_eggs > 0) {
            foreach (['parent_flock' => ['male', 'female'], 'egg_pool' => [null]] as $type => $genders) {
                foreach ($genders as $gender) {
                    $query = InventoryCostMovement::query()
                        ->where('inventory_type', $type)
                        ->where('inventory_id', $record->parent_flock_id)
                        ->where('gender', $gender)
                        ->whereDate('moved_on', '>=', $record->recorded_on)
                        ->whereNotIn('movement_type', ['opening_stock', 'opening_valuation'])
                        ->where(function ($query) use ($record): void {
                            $query->whereNull('source_type')
                                ->orWhereNull('source_id')
                                ->orWhere('source_type', '!=', $record->getMorphClass())
                                ->orWhere('source_id', '!=', $record->id);
                        });

                    if ($query->exists()) {
                        throw new RecordHasDependencies(
                            'Later breeder or egg production costs depend on this collection. Nothing was removed.',
                        );
                    }
                }
            }
        }
    }

    /**
     * @param  Collection<int, InventoryCostLayer>  $layers
     */
    private function ensureSourceLayersAreUnused(string $sourceType, int $sourceId, Collection $layers): void
    {
        foreach ($layers as $layer) {
            if (InventoryCostMovement::query()
                ->where('inventory_cost_layer_id', $layer->id)
                ->where(function ($query) use ($sourceType, $sourceId): void {
                    $query->whereNull('source_type')
                        ->orWhereNull('source_id')
                        ->orWhere('source_type', '!=', $sourceType)
                        ->orWhere('source_id', '!=', $sourceId);
                })
                ->exists()) {
                throw new RecordHasDependencies(
                    'The collected egg cost layer has later cost activity. Nothing was removed.',
                );
            }
        }
    }

}

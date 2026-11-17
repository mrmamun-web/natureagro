<?php

namespace App\Services;

use App\Exceptions\RecordHasDependencies;
use App\Models\ChickBatch;
use App\Models\EggInventoryMovement;
use App\Models\EggStock;
use App\Models\IncubationBatch;
use App\Models\InventoryCostLayer;
use App\Models\InventoryCostMovement;
use App\Models\ParentFlock;
use Illuminate\Database\Eloquent\Collection;

class IncubationBatchDeletionService
{
    public function deleteWithinTransaction(IncubationBatch $batch): void
    {
        if ($batch->status !== IncubationBatch::STATUS_INCUBATING
            || ChickBatch::query()->where('source_incubation_batch_id', $batch->id)->exists()) {
            throw new RecordHasDependencies(
                'This incubation has already hatched or has a chick batch. Nothing was removed.',
            );
        }

        $flock = ParentFlock::query()->lockForUpdate()->findOrFail($batch->parent_flock_id);
        $sourceType = $batch->getMorphClass();
        $eggMovements = EggInventoryMovement::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $batch->id)
            ->lockForUpdate()
            ->get();
        $costMovements = InventoryCostMovement::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $batch->id)
            ->lockForUpdate()
            ->get();
        $sourceLayers = InventoryCostLayer::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $batch->id)
            ->lockForUpdate()
            ->get();

        $this->ensureReservationMatchesBatch($batch, $eggMovements);
        $stock = EggStock::query()
            ->where('parent_flock_id', $flock->id)
            ->lockForUpdate()
            ->first();

        if ($stock === null) {
            throw new RecordHasDependencies('The reserved egg stock is missing. Nothing was removed.');
        }

        $this->ensureCostHistoryMatchesBatch($batch, $stock, $costMovements);
        $this->ensureNoLaterEggMovements($flock->id, $eggMovements);
        $this->ensureNoLaterCostMovements($costMovements);
        $this->ensureSourceLayersAreUnused($sourceType, $batch->id, $sourceLayers);

        $reservedDelta = (int) $eggMovements->sum('reserved_delta');
        $goodDelta = (int) $eggMovements->sum('good_delta');

        if ($reservedDelta < 0
            || $stock->reserved_for_hatching < $reservedDelta
            || $goodDelta > 0) {
            throw new RecordHasDependencies(
                'The current egg stock no longer matches this incubation reservation. Nothing was removed.',
            );
        }

        $stock->good_available -= $goodDelta;
        $stock->reserved_for_hatching -= $reservedDelta;
        $stock->save();

        $affectedLayerIds = $costMovements
            ->pluck('inventory_cost_layer_id')
            ->filter()
            ->unique()
            ->values();
        $sourceLayerIds = $sourceLayers->modelKeys();

        InventoryCostMovement::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $batch->id)
            ->delete();

        if ($sourceLayerIds !== []) {
            InventoryCostLayer::query()->whereKey($sourceLayerIds)->delete();
            $affectedLayerIds = $affectedLayerIds->diff($sourceLayerIds);
        }

        app(InventoryCostLayerRecalculator::class)->recalculate($affectedLayerIds);

        EggInventoryMovement::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $batch->id)
            ->delete();

        if (! $batch->delete()) {
            throw new RecordHasDependencies('Deletion was blocked by the application. Nothing was removed.');
        }
    }

    /**
     * @param  Collection<int, EggInventoryMovement>  $movements
     */
    private function ensureReservationMatchesBatch(IncubationBatch $batch, Collection $movements): void
    {
        if ((int) $movements->sum('good_delta') !== -$batch->eggs_set
            || (int) $movements->sum('reserved_delta') !== $batch->eggs_set
            || (int) $movements->sum('damaged_delta') !== 0
            || $movements->contains(fn (EggInventoryMovement $movement): bool => $movement->movement_type !== EggInventoryMovement::TYPE_INCUBATION_RESERVE)) {
            throw new RecordHasDependencies(
                'The incubation egg history does not match its reserved quantity. Nothing was removed.',
            );
        }
    }

    /**
     * @param  Collection<int, InventoryCostMovement>  $movements
     */
    private function ensureCostHistoryMatchesBatch(IncubationBatch $batch, EggStock $stock, Collection $movements): void
    {
        $eggQuantity = (float) $movements
            ->where('inventory_type', 'egg_stock')
            ->where('inventory_id', $stock->id)
            ->sum('quantity_delta');
        $incubationQuantity = (float) $movements
            ->where('inventory_type', 'incubation_batch')
            ->where('inventory_id', $batch->id)
            ->sum('quantity_delta');

        if (abs($eggQuantity + $batch->eggs_set) > 0.0001
            || abs($incubationQuantity - $batch->eggs_set) > 0.0001) {
            throw new RecordHasDependencies(
                'The incubation cost history does not match the reserved egg quantity. Nothing was removed.',
            );
        }
    }

    /**
     * @param  Collection<int, EggInventoryMovement>  $movements
     */
    private function ensureNoLaterEggMovements(int $flockId, Collection $movements): void
    {
        if ($movements->isEmpty()) {
            throw new RecordHasDependencies('The egg reservation history is missing. Nothing was removed.');
        }

        if (EggInventoryMovement::query()
            ->where('parent_flock_id', $flockId)
            ->where('id', '>', (int) $movements->max('id'))
            ->exists()) {
            throw new RecordHasDependencies(
                'Later egg stock movements depend on this incubation. Remove or correct those later records first.',
            );
        }
    }

    /**
     * @param  Collection<int, InventoryCostMovement>  $movements
     */
    private function ensureNoLaterCostMovements(Collection $movements): void
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
                    'Later cost movements depend on this incubation. Remove or correct those later records first.',
                );
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
                    'The incubation cost layer has later cost activity. Nothing was removed.',
                );
            }
        }
    }
}

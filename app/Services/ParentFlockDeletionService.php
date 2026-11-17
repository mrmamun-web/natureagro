<?php

namespace App\Services;

use App\Exceptions\RecordHasDependencies;
use App\Models\ChickBatch;
use App\Models\EggInventoryMovement;
use App\Models\EggStock;
use App\Models\FlockDailyRecord;
use App\Models\IncubationBatch;
use App\Models\InventoryCostLayer;
use App\Models\InventoryCostMovement;
use App\Models\ParentFlock;
use App\Models\ParentFlockMovement;
use App\Models\SupplyUsage;
use Illuminate\Support\Facades\DB;

class ParentFlockDeletionService
{
    public function deleteWithinTransaction(ParentFlock $flock): void
    {
        $this->ensureNoExternalReferences($flock);

        $incubationBatches = IncubationBatch::query()
            ->where('parent_flock_id', $flock->id)
            ->lockForUpdate()
            ->get()
            ->sortByDesc(fn (IncubationBatch $batch): int => (int) EggInventoryMovement::query()
                ->where('source_type', $batch->getMorphClass())
                ->where('source_id', $batch->id)
                ->max('id'));

        foreach ($incubationBatches as $batch) {
            app(IncubationBatchDeletionService::class)->deleteWithinTransaction($batch);
        }

        $dailyRecords = FlockDailyRecord::query()
            ->where('parent_flock_id', $flock->id)
            ->orderByDesc('recorded_on')
            ->lockForUpdate()
            ->get();

        foreach ($dailyRecords as $record) {
            app(DailyFlockRecordDeletionService::class)->deleteWithinTransaction($record);
        }

        $eggStock = EggStock::query()
            ->where('parent_flock_id', $flock->id)
            ->lockForUpdate()
            ->first();

        if (EggInventoryMovement::query()->where('parent_flock_id', $flock->id)->exists()
            || IncubationBatch::query()->where('parent_flock_id', $flock->id)->exists()
            || FlockDailyRecord::query()->where('parent_flock_id', $flock->id)->exists()) {
            throw new RecordHasDependencies(
                'This flock still has unhandled egg, incubation, or daily history. Nothing was removed.',
            );
        }

        if ($eggStock !== null
            && ($eggStock->good_available !== 0
                || $eggStock->damaged !== 0
                || $eggStock->reserved_for_hatching !== 0)) {
            throw new RecordHasDependencies(
                'This flock still has egg stock after removing its test records. Nothing was removed.',
            );
        }

        $this->ensureNoUnrecognizedCostMovements($flock, $eggStock);

        if ($eggStock !== null) {
            InventoryCostLayer::query()
                ->where('inventory_type', 'egg_stock')
                ->where('inventory_id', $eggStock->id)
                ->delete();
            $eggStock->delete();
        }

        InventoryCostMovement::query()
            ->where('inventory_type', 'parent_flock')
            ->where('inventory_id', $flock->id)
            ->delete();
        InventoryCostLayer::query()
            ->where('inventory_type', 'parent_flock')
            ->where('inventory_id', $flock->id)
            ->delete();
        InventoryCostMovement::query()
            ->where('inventory_type', 'egg_pool')
            ->where('inventory_id', $flock->id)
            ->delete();

        ParentFlockMovement::query()->where('parent_flock_id', $flock->id)->delete();

        $deleted = ParentFlock::withoutEvents(fn (): bool => $flock->delete());

        if (! $deleted) {
            throw new RecordHasDependencies('Deletion was blocked by the application. Nothing was removed.');
        }
    }

    private function ensureNoExternalReferences(ParentFlock $flock): void
    {
        if (SupplyUsage::query()->where('parent_flock_id', $flock->id)->exists()) {
            throw new RecordHasDependencies(
                'This flock has feed or medicine usage records. Delete those usages first; nothing was removed.',
            );
        }

        if (DB::table('sales')->where('parent_flock_id', $flock->id)->exists()
            || DB::table('chick_batch_movements')->where('target_parent_flock_id', $flock->id)->exists()
            || DB::table('poultry_movements')->where('source_parent_flock_id', $flock->id)->exists()) {
            throw new RecordHasDependencies(
                'This flock is linked to sales or transfers involving other inventory. Nothing was removed.',
            );
        }

        $movements = ParentFlockMovement::query()
            ->where('parent_flock_id', $flock->id)
            ->get();

        foreach ($movements as $movement) {
            $isOpeningStock = $movement->movement_type === 'opening_stock';
            $isDailyMortality = $movement->movement_type === 'mortality'
                && $movement->flock_daily_record_id !== null;

            if (! $isOpeningStock && ! $isDailyMortality) {
                throw new RecordHasDependencies(
                    'This flock has sales, transfers, or other stock movements. Nothing was removed.',
                );
            }

            if ($movement->sale_id !== null
                || $movement->source_chick_batch_id !== null
                || $movement->source_poultry_id !== null
                || $movement->target_poultry_id !== null) {
                throw new RecordHasDependencies(
                    'This flock has sales or transfers linked to other inventory. Nothing was removed.',
                );
            }
        }

        $incubationBatches = IncubationBatch::query()
            ->where('parent_flock_id', $flock->id)
            ->get();

        foreach ($incubationBatches as $batch) {
            if ($batch->status !== IncubationBatch::STATUS_INCUBATING
                || ChickBatch::query()->where('source_incubation_batch_id', $batch->id)->exists()) {
                throw new RecordHasDependencies(
                    'This flock has a hatched or otherwise progressed incubation batch. Nothing was removed.',
                );
            }
        }
    }

    private function ensureNoUnrecognizedCostMovements(ParentFlock $flock, ?EggStock $eggStock): void
    {
        $parentMovements = InventoryCostMovement::query()
            ->where('inventory_type', 'parent_flock')
            ->where('inventory_id', $flock->id)
            ->get();

        foreach ($parentMovements as $movement) {
            $isFlockSource = $movement->source_type === ParentFlock::class
                && (int) $movement->source_id === (int) $flock->id;
            $isOpeningValuation = in_array($movement->movement_type, ['opening_stock', 'opening_valuation'], true);

            if (! $isFlockSource && ! $isOpeningValuation) {
                throw new RecordHasDependencies(
                    'This flock has cost movements linked to other records. Nothing was removed.',
                );
            }
        }

        if (InventoryCostMovement::query()
            ->where('inventory_type', 'egg_pool')
            ->where('inventory_id', $flock->id)
            ->exists()) {
            throw new RecordHasDependencies(
                'This flock still has egg production cost history. Nothing was removed.',
            );
        }

        if ($eggStock !== null
            && InventoryCostMovement::query()
                ->where('inventory_type', 'egg_stock')
                ->where('inventory_id', $eggStock->id)
                ->exists()) {
            throw new RecordHasDependencies(
                'This flock still has egg cost history linked to other activity. Nothing was removed.',
            );
        }
    }
}

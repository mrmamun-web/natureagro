<?php

namespace App\Services;

use App\Models\ChickBatch;
use App\Models\EggInventoryMovement;
use App\Models\EggStock;
use App\Models\FarmTransaction;
use App\Models\FlockDailyRecord;
use App\Models\IncubationBatch;
use App\Models\InventoryCostMovement;
use App\Models\ParentFlock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EggInventoryService
{
    public function recordCollection(FlockDailyRecord $record): void
    {
        if (($record->good_eggs + $record->damaged_eggs) === 0) {
            return;
        }

        DB::transaction(function () use ($record): void {
            $flock = ParentFlock::query()->lockForUpdate()->findOrFail($record->parent_flock_id);
            $stock = $this->lockStock($record->parent_flock_id);
            $stock->good_available += $record->good_eggs;
            $stock->damaged += $record->damaged_eggs;
            $stock->save();
            $totalCost = 0.0;

            if ($record->good_eggs > 0) {
                app(InventoryCostService::class)->allocateBreederCostToEggs(
                    $flock->id,
                    $record->good_eggs,
                    $record->recorded_on->toDateString(),
                    $record,
                );
                $totalCost = (float) InventoryCostMovement::query()
                    ->where('inventory_type', 'egg_pool')
                    ->where('inventory_id', $flock->id)
                    ->whereDate('moved_on', '<=', $record->recorded_on)
                    ->sum('cost_delta');
                $totalCost = max(0, round($totalCost, 2));
                $costs = app(InventoryCostService::class);
                $costs->consumeEggProductionCost($flock->id, $totalCost, $record->recorded_on->toDateString(), $record);
                $costs->receive(
                    'egg_stock',
                    $stock->id,
                    null,
                    $record->good_eggs,
                    $totalCost,
                    $record->recorded_on->toDateString(),
                    'egg_collection',
                    $record,
                    'FIFO production cost assigned to collected good eggs.',
                );
            }

            EggInventoryMovement::query()->create([
                'parent_flock_id' => $record->parent_flock_id,
                'movement_type' => EggInventoryMovement::TYPE_COLLECTION,
                'good_delta' => $record->good_eggs,
                'damaged_delta' => $record->damaged_eggs,
                'source_type' => FlockDailyRecord::class,
                'source_id' => $record->id,
                'total_cost' => $totalCost,
                'notes' => 'Eggs collected from daily flock record.',
            ]);
        });
    }

    public function reserveForIncubation(IncubationBatch $batch): void
    {
        DB::transaction(function () use ($batch): void {
            $stock = $this->lockStock($batch->parent_flock_id);

            if ($stock->good_available < $batch->eggs_set) {
                throw ValidationException::withMessages([
                    'data.eggs_set' => 'There are not enough available good eggs in this flock.',
                ]);
            }

            $stock->good_available -= $batch->eggs_set;
            $stock->reserved_for_hatching += $batch->eggs_set;
            $stock->save();
            $costs = app(InventoryCostService::class);
            $costDate = Carbon::parse($batch->incubated_on)
                ->max(today())
                ->toDateString();
            $consumed = $costs->consume(
                'egg_stock',
                $stock->id,
                null,
                $batch->eggs_set,
                $costDate,
                'incubation_reserve',
                $batch,
                'Eggs reserved for this incubation batch.',
            );
            $costs->receiveTransfer(
                'incubation_batch',
                $batch->id,
                null,
                $consumed['allocations'],
                $costDate,
                $batch,
                'FIFO egg value transferred into incubation.',
            );

            EggInventoryMovement::query()->create([
                'parent_flock_id' => $batch->parent_flock_id,
                'movement_type' => EggInventoryMovement::TYPE_INCUBATION_RESERVE,
                'good_delta' => -$batch->eggs_set,
                'reserved_delta' => $batch->eggs_set,
                'source_type' => IncubationBatch::class,
                'source_id' => $batch->id,
                'total_cost' => $consumed['total_cost'],
                'notes' => 'Eggs reserved for incubation.',
            ]);
        });
    }

    public function recordHatchOutcome(int $incubationBatchId, array $data): ?ChickBatch
    {
        return DB::transaction(function () use ($incubationBatchId, $data): ?ChickBatch {
            $batch = IncubationBatch::query()
                ->lockForUpdate()
                ->findOrFail($incubationBatchId);

            if ($batch->status !== IncubationBatch::STATUS_INCUBATING) {
                throw ValidationException::withMessages([
                    'data.actual_hatch_on' => 'A hatch outcome can only be recorded once.',
                ]);
            }

            $hatched = (int) $data['hatched_chicks'];
            $failed = (int) $data['failed_eggs'];

            if (($hatched + $failed) !== $batch->eggs_set) {
                throw ValidationException::withMessages([
                    'data.failed_eggs' => 'Hatched chicks plus failed eggs must equal the eggs set for incubation.',
                ]);
            }

            $stock = $this->lockStock($batch->parent_flock_id);

            if ($stock->reserved_for_hatching < $batch->eggs_set) {
                throw ValidationException::withMessages([
                    'data.actual_hatch_on' => 'Reserved egg stock does not match this incubation batch.',
                ]);
            }

            $reservedOn = InventoryCostMovement::query()
                ->where('inventory_type', 'incubation_batch')
                ->where('inventory_id', $batch->id)
                ->whereNull('gender')
                ->where('movement_type', 'transfer_in')
                ->max('moved_on');
            $costDate = Carbon::parse($data['actual_hatch_on'])
                ->max(Carbon::parse($reservedOn ?? $batch->incubated_on))
                ->toDateString();

            $consumed = app(InventoryCostService::class)->consume(
                'incubation_batch',
                $batch->id,
                null,
                $batch->eggs_set,
                $costDate,
                'hatch_outcome',
                $batch,
                'Incubation egg cost transferred to hatched chicks.',
            );

            $stock->reserved_for_hatching -= $batch->eggs_set;
            $stock->save();

            EggInventoryMovement::query()->create([
                'parent_flock_id' => $batch->parent_flock_id,
                'movement_type' => EggInventoryMovement::TYPE_HATCH_OUTCOME,
                'reserved_delta' => -$batch->eggs_set,
                'source_type' => IncubationBatch::class,
                'source_id' => $batch->id,
                'total_cost' => $consumed['total_cost'],
                'notes' => "Hatch outcome: {$hatched} hatched, {$failed} failed.",
            ]);

            $batch->update([
                'hatched_chicks' => $hatched,
                'failed_eggs' => $failed,
                'actual_hatch_on' => $data['actual_hatch_on'],
                'status' => IncubationBatch::STATUS_HATCHED,
            ]);

            if ($hatched === 0) {
                if ($consumed['total_cost'] > 0) {
                    FarmTransaction::query()->create([
                        'transaction_type' => FarmTransaction::TYPE_EXPENSE,
                        'category' => 'production_loss',
                        'amount' => number_format($consumed['total_cost'], 2, '.', ''),
                                'transaction_on' => $data['actual_hatch_on'],
                        'reference' => $batch->batch_code,
                        'notes' => 'Incubation batch produced no chicks; FIFO egg production cost was recognized as a loss.',
                    ]);
                }

                return null;
            }

            $chickBatch = ChickBatch::query()->create([
                'name' => $batch->parentFlock->name.' hatch batch',
                'breed' => $batch->parentFlock->breed,
                'source' => ChickBatch::SOURCE_HATCHED,
                'source_incubation_batch_id' => $batch->id,
                'hatch_date' => $data['actual_hatch_on'],
                'batch_month' => $batch->incubation_month ?? Carbon::parse($data['actual_hatch_on'])->month,
                'initial_quantity' => $hatched,
                'status' => ChickBatch::STATUS_ACTIVE,
            ]);

            app(InventoryCostService::class)->receive(
                'chick_batch',
                $chickBatch->id,
                null,
                $hatched,
                $consumed['total_cost'],
                $costDate,
                'hatch_output',
                $batch,
                'Egg production and incubation cost assigned to hatched chicks.',
            );

            return $chickBatch;
        });
    }

    private function lockStock(int $parentFlockId): EggStock
    {
        ParentFlock::query()
            ->lockForUpdate()
            ->findOrFail($parentFlockId);

        $stock = EggStock::query()
            ->where('parent_flock_id', $parentFlockId)
            ->lockForUpdate()
            ->first();

        if ($stock !== null) {
            return $stock;
        }

        return EggStock::query()->create([
            'parent_flock_id' => $parentFlockId,
        ]);
    }
}

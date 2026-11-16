<?php

namespace App\Services;

use App\Models\ChickBatch;
use App\Models\ChickBatchMovement;
use App\Models\ParentFlock;
use App\Models\Poultry;
use App\Models\PoultryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ChickBatchService
{
    public function recordMortality(ChickBatch $batch, array $data): ChickBatch
    {
        $data = $this->validate($data, [
            'quantity' => ['required', 'integer', 'min:1'],
            'moved_on' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($batch, $data): ChickBatch {
            $lockedBatch = ChickBatch::query()->lockForUpdate()->findOrFail($batch->id);
            $this->ensureAvailable($lockedBatch, (int) $data['quantity']);
            $cost = app(InventoryCostService::class)->consume(
                'chick_batch',
                $lockedBatch->id,
                null,
                (int) $data['quantity'],
                $data['moved_on'],
                'mortality',
                $lockedBatch,
                'Chick batch mortality.',
            )['total_cost'];

            $lockedBatch->decrement('current_quantity', (int) $data['quantity']);
            $lockedBatch->movements()->create([
                'movement_type' => ChickBatchMovement::TYPE_MORTALITY,
                'quantity' => $data['quantity'],
                'total_cost' => number_format($cost, 2, '.', ''),
                'moved_on' => $data['moved_on'],
                'notes' => $data['notes'] ?? null,
            ]);

            return $lockedBatch->refresh();
        });
    }

    public function transfer(ChickBatch $batch, array $data): ChickBatch
    {
        $targetType = $data['target_type'] ?? null;
        $data = $this->validate($data, [
            'target_type' => ['required', Rule::in(['chick_batch', 'parent_flock', 'poultry'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'gender' => [Rule::requiredIf($targetType === 'parent_flock'), 'nullable', Rule::in(['male', 'female'])],
            'target_chick_batch_id' => [Rule::requiredIf($targetType === 'chick_batch'), 'nullable', 'integer', 'exists:chick_batches,id'],
            'target_parent_flock_id' => [Rule::requiredIf($targetType === 'parent_flock'), 'nullable', 'integer', 'exists:parent_flocks,id'],
            'target_poultry_id' => [Rule::requiredIf($targetType === 'poultry'), 'nullable', 'integer', 'exists:poultries,id'],
            'moved_on' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($batch, $data): ChickBatch {
            $batchIds = [$batch->id];
            if ($data['target_type'] === 'chick_batch') {
                $targetBatchId = (int) $data['target_chick_batch_id'];

                if ($targetBatchId === (int) $batch->id) {
                    throw ValidationException::withMessages([
                        'data.target_chick_batch_id' => 'A batch cannot receive a transfer from itself.',
                    ]);
                }

                $batchIds[] = $targetBatchId;
            }

            $lockedBatches = ChickBatch::query()
                ->whereIn('id', $batchIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $lockedBatch = $lockedBatches->get($batch->id);
            if ($lockedBatch === null) {
                throw ValidationException::withMessages([
                    'data.quantity' => 'The source chick batch no longer exists.',
                ]);
            }

            $data['source_chick_batch_id'] = $lockedBatch->id;
            $quantity = (int) $data['quantity'];
            $this->ensureAvailable($lockedBatch, $quantity);
            $costs = app(InventoryCostService::class);
            $consumed = $costs->consume(
                'chick_batch',
                $lockedBatch->id,
                null,
                $quantity,
                $data['moved_on'],
                'transfer_out',
                $lockedBatch,
                'Transferred from chick batch '.$lockedBatch->batch_code.'.',
            );

            $movement = [
                'movement_type' => ChickBatchMovement::TYPE_TRANSFER,
                'quantity' => $quantity,
                'total_cost' => number_format($consumed['total_cost'], 2, '.', ''),
                'gender' => $data['gender'] ?? null,
                'moved_on' => $data['moved_on'],
                'notes' => $data['notes'] ?? null,
            ];

            $targetBatch = match ($data['target_type']) {
                'chick_batch' => $this->transferToChickBatch($lockedBatches->get($data['target_chick_batch_id']), $quantity, $consumed['allocations'], $data['moved_on']),
                'parent_flock' => $this->transferToParentFlock($data, $quantity, $consumed['allocations']),
                'poultry' => $this->transferToPoultry($data, $quantity, $consumed['allocations']),
            };

            $lockedBatch->decrement('current_quantity', $quantity);
            $lockedBatch->movements()->create($movement + [
                'target_chick_batch_id' => $data['target_chick_batch_id'] ?? null,
                'target_parent_flock_id' => $data['target_parent_flock_id'] ?? null,
                'target_poultry_id' => $data['target_poultry_id'] ?? null,
            ]);

            if ($targetBatch instanceof ChickBatch) {
                $targetBatch->movements()->create([
                    'movement_type' => ChickBatchMovement::TYPE_TRANSFER_RECEIVED,
                    'quantity' => $quantity,
                    'source_chick_batch_id' => $lockedBatch->id,
                    'total_cost' => number_format($consumed['total_cost'], 2, '.', ''),
                    'moved_on' => $data['moved_on'],
                    'notes' => $data['notes'] ?? null,
                ]);
            }

            return $lockedBatch->refresh();
        });
    }

    public function archive(ChickBatch $batch): ChickBatch
    {
        return DB::transaction(function () use ($batch): ChickBatch {
            $lockedBatch = ChickBatch::query()->lockForUpdate()->findOrFail($batch->id);

            if ($lockedBatch->status !== ChickBatch::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'data.status' => 'Only active batches can be archived.',
                ]);
            }

            if ($lockedBatch->hatch_date->addMonths(6)->isFuture()) {
                throw ValidationException::withMessages([
                    'data.status' => 'A batch can only be archived after reaching six months of age.',
                ]);
            }

            if ($lockedBatch->current_quantity !== 0) {
                throw ValidationException::withMessages([
                    'data.status' => 'Sell, transfer, or otherwise account for every remaining bird before archiving.',
                ]);
            }

            $lockedBatch->update(['status' => ChickBatch::STATUS_ARCHIVED]);
            $lockedBatch->movements()->create([
                'movement_type' => ChickBatchMovement::TYPE_ARCHIVE,
                'moved_on' => today(),
                'notes' => 'Batch archived after six months; remaining stock is zero.',
            ]);

            return $lockedBatch->refresh();
        });
    }

    private function transferToChickBatch(?ChickBatch $target, int $quantity, array $allocations, string $date): ?ChickBatch
    {
        if ($target === null || $target->status !== ChickBatch::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'data.target_chick_batch_id' => 'Select an active chick batch.',
            ]);
        }

        $target->increment('current_quantity', $quantity);
        app(InventoryCostService::class)->receiveTransfer(
            'chick_batch',
            $target->id,
            null,
            $allocations,
            $date,
            $target,
            'FIFO bird cost transferred into chick batch.',
        );

        return $target->refresh();
    }

    private function transferToParentFlock(array $data, int $quantity, array $allocations): ?ChickBatch
    {
        $flock = ParentFlock::query()->lockForUpdate()->findOrFail($data['target_parent_flock_id']);

        if ($flock->status !== ParentFlock::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'data.target_parent_flock_id' => 'Select an active parent flock.',
            ]);
        }

        $column = $data['gender'] === 'female' ? 'current_female_quantity' : 'current_male_quantity';
        $flock->increment($column, $quantity);
        $cost = app(InventoryCostService::class)->receiveTransfer(
            'parent_flock',
            $flock->id,
            $data['gender'],
            $allocations,
            $data['moved_on'],
            $flock,
            'FIFO bird cost transferred from chick batch.',
        );
        $flock->movements()->create([
            'movement_type' => PoultryMovement::TYPE_TRANSFER_IN,
            'quantity' => $quantity,
            'total_cost' => number_format($cost, 2, '.', ''),
            'male_quantity' => $data['gender'] === 'male' ? $quantity : 0,
            'female_quantity' => $data['gender'] === 'female' ? $quantity : 0,
            'source_chick_batch_id' => $data['source_chick_batch_id'] ?? null,
            'moved_on' => $data['moved_on'],
            'notes' => $data['notes'] ?? 'Transferred from a chick batch.',
        ]);

        return null;
    }

    private function transferToPoultry(array $data, int $quantity, array $allocations): ?ChickBatch
    {
        $poultry = Poultry::query()->lockForUpdate()->findOrFail($data['target_poultry_id']);

        if ($poultry->status !== Poultry::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'data.target_poultry_id' => 'Select an active poultry record.',
            ]);
        }

        $poultry->increment('current_quantity', $quantity);
        $cost = app(InventoryCostService::class)->receiveTransfer(
            'poultry',
            $poultry->id,
            null,
            $allocations,
            $data['moved_on'],
            $poultry,
            'FIFO bird cost transferred from chick batch.',
        );
        $poultry->movements()->create([
            'movement_type' => 'transfer_in',
            'quantity' => $quantity,
            'total_cost' => number_format($cost, 2, '.', ''),
            'source_chick_batch_id' => $data['source_chick_batch_id'] ?? null,
            'moved_on' => $data['moved_on'],
            'notes' => $data['notes'] ?? 'Transferred from a chick batch.',
        ]);

        return null;
    }

    private function ensureAvailable(ChickBatch $batch, int $quantity): void
    {
        if ($batch->status !== ChickBatch::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'data.quantity' => 'Only active chick batches can be changed.',
            ]);
        }

        if ($quantity < 1 || $quantity > $batch->current_quantity) {
            throw ValidationException::withMessages([
                'data.quantity' => 'Quantity must not exceed the current live stock.',
            ]);
        }
    }

    private function validate(array $data, array $rules): array
    {
        try {
            return Validator::make($data, $rules)->validate();
        } catch (ValidationException $exception) {
            $errors = [];
            foreach ($exception->errors() as $field => $messages) {
                $errors['data.'.$field] = $messages;
            }

            throw ValidationException::withMessages($errors);
        }
    }
}

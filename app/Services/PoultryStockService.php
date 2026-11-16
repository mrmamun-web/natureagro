<?php

namespace App\Services;

use App\Models\ParentFlock;
use App\Models\Poultry;
use App\Models\PoultryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PoultryStockService
{
    public function recordMortality(Poultry $poultry, array $data): Poultry
    {
        $data = $this->validate($data, [
            'quantity' => ['required', 'integer', 'min:1'],
            'moved_on' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($poultry, $data): Poultry {
            $locked = Poultry::query()->lockForUpdate()->findOrFail($poultry->id);
            $this->ensureAvailable($locked, (int) $data['quantity']);
            $cost = app(InventoryCostService::class)->consume(
                'poultry',
                $locked->id,
                null,
                (int) $data['quantity'],
                $data['moved_on'],
                'mortality',
                $locked,
                'General poultry mortality.',
            )['total_cost'];
            $locked->decrement('current_quantity', (int) $data['quantity']);
            if ($locked->fresh()->current_quantity === 0) {
                $locked->update(['status' => Poultry::STATUS_DECEASED]);
            }
            $locked->movements()->create([
                'movement_type' => PoultryMovement::TYPE_MORTALITY,
                'quantity' => $data['quantity'],
                'total_cost' => number_format($cost, 2, '.', ''),
                'moved_on' => $data['moved_on'],
                'notes' => $data['notes'] ?? null,
            ]);

            return $locked->refresh();
        });
    }

    public function transferToParentFlock(Poultry $poultry, array $data): Poultry
    {
        $data = $this->validate($data, [
            'quantity' => ['required', 'integer', 'min:1'],
            'target_parent_flock_id' => ['required', 'integer', 'exists:parent_flocks,id'],
            'moved_on' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($poultry, $data): Poultry {
            $flock = ParentFlock::query()->lockForUpdate()->findOrFail($data['target_parent_flock_id']);
            $locked = Poultry::query()->lockForUpdate()->findOrFail($poultry->id);
            $this->ensureAvailable($locked, (int) $data['quantity']);

            if ($flock->status !== ParentFlock::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'data.target_parent_flock_id' => 'Select an active parent flock.',
                ]);
            }

            $quantity = (int) $data['quantity'];
            $isRooster = $locked->type === Poultry::TYPE_ROOSTER;
            $gender = $isRooster ? 'male' : 'female';
            $consumed = app(InventoryCostService::class)->consume(
                'poultry',
                $locked->id,
                null,
                $quantity,
                $data['moved_on'],
                'transfer_out',
                $locked,
                'Transferred from poultry inventory '.$locked->poultry_code.'.',
            );

            $flockColumn = $isRooster ? 'current_male_quantity' : 'current_female_quantity';
            $locked->decrement('current_quantity', $quantity);
            if ($locked->fresh()->current_quantity === 0) {
                $locked->update(['status' => Poultry::STATUS_SOLD]);
            }
            $flock->increment($flockColumn, $quantity);
            $cost = app(InventoryCostService::class)->receiveTransfer(
                'parent_flock',
                $flock->id,
                $gender,
                $consumed['allocations'],
                $data['moved_on'],
                $flock,
                'FIFO bird cost transferred from poultry inventory.',
            );

            $locked->movements()->create([
                'movement_type' => PoultryMovement::TYPE_TRANSFER_OUT,
                'quantity' => $quantity,
                'total_cost' => number_format($consumed['total_cost'], 2, '.', ''),
                'moved_on' => $data['moved_on'],
                'notes' => $data['notes'] ?? 'Transferred to parent flock '.$flock->flock_code.'.',
            ]);
            $flock->movements()->create([
                'movement_type' => 'transfer_in',
                'quantity' => $quantity,
                'total_cost' => number_format($cost, 2, '.', ''),
                'male_quantity' => $isRooster ? $quantity : 0,
                'female_quantity' => $isRooster ? 0 : $quantity,
                'source_poultry_id' => $locked->id,
                'moved_on' => $data['moved_on'],
                'notes' => $data['notes'] ?? 'Transferred from poultry inventory '.$locked->poultry_code.'.',
            ]);

            return $locked->refresh();
        });
    }

    private function ensureAvailable(Poultry $poultry, int $quantity): void
    {
        if ($poultry->status !== Poultry::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'data.quantity' => 'Only active poultry inventory can be changed.',
            ]);
        }

        if ($quantity < 1 || $quantity > $poultry->current_quantity) {
            throw ValidationException::withMessages([
                'data.quantity' => 'Quantity must not exceed the current available stock.',
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

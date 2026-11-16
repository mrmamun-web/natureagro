<?php

namespace App\Services;

use App\Models\ParentFlock;
use App\Models\Poultry;
use App\Models\PoultryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ParentFlockService
{
    public function transferToPoultryInventory(ParentFlock $flock, array $data): ParentFlock
    {
        $data = $this->validate($data, [
            'gender' => ['required', 'in:male,female'],
            'quantity' => ['required', 'integer', 'min:1'],
            'target_poultry_id' => ['required', 'integer', 'exists:poultries,id'],
            'moved_on' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($flock, $data): ParentFlock {
            $lockedFlock = ParentFlock::query()->lockForUpdate()->findOrFail($flock->id);
            $target = Poultry::query()->lockForUpdate()->findOrFail($data['target_poultry_id']);

            if ($lockedFlock->status !== ParentFlock::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'data.parent_flock_id' => 'Only active parent flocks can be transferred.',
                ]);
            }

            if ($target->status !== Poultry::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'data.target_poultry_id' => 'Select an active poultry record.',
                ]);
            }

            $isMale = $data['gender'] === 'male';
            $expectedType = $isMale ? Poultry::TYPE_ROOSTER : Poultry::TYPE_CHICKEN;
            if ($target->type !== $expectedType) {
                throw ValidationException::withMessages([
                    'data.target_poultry_id' => 'The destination inventory type must match the selected sex.',
                ]);
            }

            $quantity = (int) $data['quantity'];
            $flockColumn = $isMale ? 'current_male_quantity' : 'current_female_quantity';
            if ($quantity > $lockedFlock->{$flockColumn}) {
                throw ValidationException::withMessages([
                    'data.quantity' => 'Transfer quantity cannot exceed the current flock stock for that sex.',
                ]);
            }

            $costs = app(InventoryCostService::class);
            $consumed = $costs->consume(
                'parent_flock',
                $lockedFlock->id,
                $data['gender'],
                $quantity,
                $data['moved_on'],
                'transfer_out',
                $lockedFlock,
                'Transferred from parent flock '.$lockedFlock->flock_code.'.',
            );
            $lockedFlock->decrement($flockColumn, $quantity);
            $target->increment('current_quantity', $quantity);
            $cost = $costs->receiveTransfer(
                'poultry',
                $target->id,
                null,
                $consumed['allocations'],
                $data['moved_on'],
                $target,
                'FIFO bird cost transferred from parent flock.',
            );
            $lockedFlock->movements()->create([
                'movement_type' => 'transfer_out',
                'quantity' => $quantity,
                'total_cost' => number_format($consumed['total_cost'], 2, '.', ''),
                'male_quantity' => $isMale ? $quantity : 0,
                'female_quantity' => $isMale ? 0 : $quantity,
                'target_poultry_id' => $target->id,
                'moved_on' => $data['moved_on'],
                'notes' => $data['notes'] ?? 'Transferred to poultry inventory '.$target->poultry_code.'.',
            ]);
            $target->movements()->create([
                'movement_type' => PoultryMovement::TYPE_TRANSFER_IN,
                'quantity' => $quantity,
                'total_cost' => number_format($cost, 2, '.', ''),
                'source_parent_flock_id' => $lockedFlock->id,
                'moved_on' => $data['moved_on'],
                'notes' => $data['notes'] ?? 'Transferred from parent flock '.$lockedFlock->flock_code.'.',
            ]);

            return $lockedFlock->refresh();
        });
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

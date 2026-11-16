<?php

namespace App\Filament\Resources\ParentFlocks\Pages;

use App\Filament\Resources\ParentFlocks\ParentFlockResource;
use App\Models\ParentFlock;
use App\Services\InventoryCostService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateParentFlock extends CreateRecord
{
    protected static string $resource = ParentFlockResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (((int) $data['initial_male_quantity'] + (int) $data['initial_female_quantity']) < 1) {
            throw ValidationException::withMessages([
                'data.initial_female_quantity' => 'A flock must start with at least one bird.',
            ]);
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): ParentFlock {
            $maleUnitCost = isset($data['initial_male_unit_cost']) ? (float) $data['initial_male_unit_cost'] : null;
            $femaleUnitCost = isset($data['initial_female_unit_cost']) ? (float) $data['initial_female_unit_cost'] : null;
            unset($data['initial_male_unit_cost'], $data['initial_female_unit_cost']);

            $flock = ParentFlock::query()->create($data);
            $costs = app(InventoryCostService::class);
            $purchasedOn = $flock->purchase_date?->toDateString() ?? today()->toDateString();

            if ($flock->initial_male_quantity > 0) {
                $costs->receive(
                    'parent_flock',
                    $flock->id,
                    'male',
                    $flock->initial_male_quantity,
                    $maleUnitCost === null ? null : $maleUnitCost * $flock->initial_male_quantity,
                    $purchasedOn,
                    'purchase',
                    $flock,
                    'Initial male parent flock valuation.',
                );
            }

            if ($flock->initial_female_quantity > 0) {
                $costs->receive(
                    'parent_flock',
                    $flock->id,
                    'female',
                    $flock->initial_female_quantity,
                    $femaleUnitCost === null ? null : $femaleUnitCost * $flock->initial_female_quantity,
                    $purchasedOn,
                    'purchase',
                    $flock,
                    'Initial female parent flock valuation.',
                );
            }

            return $flock;
        });
    }
}

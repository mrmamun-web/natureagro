<?php

namespace App\Filament\Resources\ParentFlocks\Pages;

use App\Filament\Resources\ParentFlocks\ParentFlockResource;
use App\Models\ParentFlock;
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
            return ParentFlock::query()->create($data);
        });
    }
}

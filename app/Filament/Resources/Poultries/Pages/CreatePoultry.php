<?php

namespace App\Filament\Resources\Poultries\Pages;

use App\Filament\Resources\Poultries\PoultryResource;
use App\Models\Poultry;
use App\Services\InventoryCostService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreatePoultry extends CreateRecord
{
    protected static string $resource = PoultryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Poultry {
            $poultry = Poultry::query()->create($data);
            app(InventoryCostService::class)->receive(
                'poultry',
                $poultry->id,
                null,
                $poultry->initial_quantity,
                $poultry->unit_purchase_price === null
                    ? null
                    : (float) $poultry->unit_purchase_price * $poultry->initial_quantity,
                $poultry->purchase_date?->toDateString() ?? today()->toDateString(),
                'purchase',
                $poultry,
                'Initial poultry inventory valuation.',
            );

            return $poultry;
        });
    }
}

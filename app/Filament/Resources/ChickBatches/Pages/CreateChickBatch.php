<?php

namespace App\Filament\Resources\ChickBatches\Pages;

use App\Filament\Resources\ChickBatches\ChickBatchResource;
use App\Models\ChickBatch;
use App\Services\InventoryCostService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateChickBatch extends CreateRecord
{
    protected static string $resource = ChickBatchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['source'] = ChickBatch::SOURCE_PURCHASED;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): ChickBatch {
            $unitCost = (float) $data['initial_unit_cost'];
            unset($data['initial_unit_cost']);

            $batch = ChickBatch::query()->create($data);
            app(InventoryCostService::class)->receive(
                'chick_batch',
                $batch->id,
                null,
                $batch->initial_quantity,
                $unitCost * $batch->initial_quantity,
                $batch->hatch_date->toDateString(),
                'purchase',
                $batch,
                'Purchased chick batch initial valuation.',
            );

            return $batch;
        });
    }
}

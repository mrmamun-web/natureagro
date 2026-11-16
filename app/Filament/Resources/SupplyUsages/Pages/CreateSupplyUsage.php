<?php

namespace App\Filament\Resources\SupplyUsages\Pages;

use App\Filament\Resources\SupplyUsages\SupplyUsageResource;
use App\Services\SupplyInventoryService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSupplyUsage extends CreateRecord
{
    protected static string $resource = SupplyUsageResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SupplyInventoryService::class)->recordUsage($data);
    }
}

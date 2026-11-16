<?php

namespace App\Filament\Resources\SupplyPurchases\Pages;

use App\Filament\Resources\SupplyPurchases\SupplyPurchaseResource;
use App\Services\SupplyInventoryService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSupplyPurchase extends CreateRecord
{
    protected static string $resource = SupplyPurchaseResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SupplyInventoryService::class)->createPurchase($data);
    }
}

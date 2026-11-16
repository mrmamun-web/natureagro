<?php

namespace App\Filament\Resources\SupplyPurchases\Pages;

use App\Filament\Resources\SupplyPurchases\SupplyPurchaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplyPurchases extends ListRecords
{
    protected static string $resource = SupplyPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

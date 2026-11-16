<?php

namespace App\Filament\Resources\SupplyStockMovements\Pages;

use App\Filament\Resources\SupplyStockMovements\SupplyStockMovementResource;
use Filament\Resources\Pages\ListRecords;

class ListSupplyStockMovements extends ListRecords
{
    protected static string $resource = SupplyStockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

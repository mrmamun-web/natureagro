<?php

namespace App\Filament\Resources\SupplyProducts\Pages;

use App\Filament\Resources\SupplyProducts\SupplyProductResource;
use Filament\Resources\Pages\EditRecord;

class EditSupplyProduct extends EditRecord
{
    protected static string $resource = SupplyProductResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

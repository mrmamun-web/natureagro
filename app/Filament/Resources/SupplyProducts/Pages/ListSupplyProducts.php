<?php

namespace App\Filament\Resources\SupplyProducts\Pages;

use App\Filament\Resources\SupplyProducts\SupplyProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplyProducts extends ListRecords
{
    protected static string $resource = SupplyProductResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

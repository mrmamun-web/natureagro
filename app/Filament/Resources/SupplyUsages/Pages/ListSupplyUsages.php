<?php

namespace App\Filament\Resources\SupplyUsages\Pages;

use App\Filament\Resources\SupplyUsages\SupplyUsageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplyUsages extends ListRecords
{
    protected static string $resource = SupplyUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

<?php

namespace App\Filament\Resources\ChickBatches\Pages;

use App\Filament\Resources\ChickBatches\ChickBatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChickBatches extends ListRecords
{
    protected static string $resource = ChickBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

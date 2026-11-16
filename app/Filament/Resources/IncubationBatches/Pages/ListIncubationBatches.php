<?php

namespace App\Filament\Resources\IncubationBatches\Pages;

use App\Filament\Resources\IncubationBatches\IncubationBatchResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListIncubationBatches extends ListRecords
{
    protected static string $resource = IncubationBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('New Incubation Batch')
                ->icon('heroicon-o-plus')
                ->button()
                ->url(IncubationBatchResource::getUrl('create')),
        ];
    }
}

<?php

namespace App\Filament\Resources\Poultries\Pages;

use App\Filament\Resources\Poultries\PoultryResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListPoultries extends ListRecords
{
    protected static string $resource = PoultryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('New Poultry')
                ->icon('heroicon-o-plus')
                ->button()
                ->url(PoultryResource::getUrl('create')),
        ];
    }
}

<?php

namespace App\Filament\Resources\ParentFlocks\Pages;

use App\Filament\Resources\ParentFlocks\ParentFlockResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListParentFlocks extends ListRecords
{
    protected static string $resource = ParentFlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('New Parent Flock')
                ->icon('heroicon-o-plus')
                ->button()
                ->url(ParentFlockResource::getUrl('create')),
        ];
    }
}

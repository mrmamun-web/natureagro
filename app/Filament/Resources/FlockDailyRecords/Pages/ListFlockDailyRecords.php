<?php

namespace App\Filament\Resources\FlockDailyRecords\Pages;

use App\Filament\Resources\FlockDailyRecords\FlockDailyRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFlockDailyRecords extends ListRecords
{
    protected static string $resource = FlockDailyRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

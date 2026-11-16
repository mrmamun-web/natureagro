<?php

namespace App\Filament\Resources\FarmTransactions\Pages;

use App\Filament\Resources\FarmTransactions\FarmTransactionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListFarmTransactions extends ListRecords
{
    protected static string $resource = FarmTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('New Farm Transaction')
                ->icon('heroicon-o-plus')
                ->button()
                ->url(FarmTransactionResource::getUrl('create')),
        ];
    }
}

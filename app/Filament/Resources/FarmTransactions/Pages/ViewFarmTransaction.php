<?php

namespace App\Filament\Resources\FarmTransactions\Pages;

use App\Filament\Resources\FarmTransactions\FarmTransactionResource;
use App\Services\FarmTransactionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewFarmTransaction extends ViewRecord
{
    protected static string $resource = FarmTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reverse')
                ->label('Reverse transaction')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('This creates a new opposite transaction; the original entry remains unchanged.')
                ->visible(fn (): bool => $this->getRecord()->reversal_of_id === null && ! $this->getRecord()->reversal()->exists())
                ->action(function (): void {
                    $this->record = app(FarmTransactionService::class)->reverse($this->getRecord());
                    Notification::make()->success()->title('Reversal recorded. Both entries are preserved.')->send();
                }),
        ];
    }
}

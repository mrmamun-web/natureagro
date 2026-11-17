<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use App\Services\SaleService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printInvoice')
                ->label('Print invoice PDF')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => Filament::getPanel('admin')->route('sales.invoice', [
                    'sale' => $this->getRecord(),
                ]))
                ->openUrlInNewTab(),

            Action::make('recordPayment')
                ->label('Record Payment')
                ->icon('heroicon-o-banknotes')
                ->visible(fn (): bool => (float) $this->getRecord()->due_amount > 0)
                ->form([
                    TextInput::make('amount')
                        ->label('Payment Amount (৳)')
                        ->numeric()
                        ->minValue(0.01)
                        ->maxValue(fn (): float => (float) $this->getRecord()->due_amount)
                        ->prefix('৳')
                        ->required(),

                    DatePicker::make('paid_on')
                        ->label('Payment Date')
                        ->default(now()->toDateString())
                        ->maxDate(now())
                        ->native(false)
                        ->required(),

                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->options([
                            'cash' => 'Cash',
                            'bkash' => 'bKash',
                            'nagad' => 'Nagad',
                            'bank' => 'Bank Transfer',
                        ])
                        ->required(),

                    TextInput::make('reference')
                        ->label('Reference')
                        ->maxLength(100),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $this->record = app(SaleService::class)->recordPayment($this->getRecord(), $data);

                    Notification::make()
                        ->success()
                        ->title('Payment recorded')
                        ->send();
                }),
        ];
    }
}

<?php

namespace App\Filament\Resources\FarmTransactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FarmTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('transaction_type')->label('Type')->badge(),
                        TextEntry::make('category')->label('Category')->badge(),
                        TextEntry::make('amount')->label('Amount')->money('BDT')->weight('bold'),
                        TextEntry::make('transaction_on')->label('Date')->date('d M Y'),
                        TextEntry::make('payment_method')->label('Payment method')->placeholder('Not recorded'),
                        TextEntry::make('reference')->label('Reference')->placeholder('—'),
                        TextEntry::make('reversedTransaction.id')->label('Reverses transaction')->prefix('#')->placeholder('Not a reversal'),
                        TextEntry::make('reversal.id')->label('Reversed by transaction')->prefix('#')->placeholder('Not reversed'),
                        TextEntry::make('notes')->label('Notes')->placeholder('No additional notes')->columnSpanFull(),
                    ]),
            ]);
    }
}

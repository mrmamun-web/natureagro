<?php

namespace App\Filament\Resources\FarmTransactions\Schemas;

use App\Models\FarmTransaction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FarmTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Other expense')
                    ->description('Record costs that are not feed, medicine, or sales.')
                    ->columns(2)
                    ->schema([
                        Hidden::make('transaction_type')
                            ->default(FarmTransaction::TYPE_EXPENSE)
                            ->required(),

                        Select::make('category')
                            ->label('Category')
                            ->options([
                                FarmTransaction::EXPENSE_LABOUR => 'Labour',
                                FarmTransaction::EXPENSE_ELECTRICITY => 'Electricity',
                                FarmTransaction::EXPENSE_TRANSPORT => 'Transportation',
                                FarmTransaction::EXPENSE_MAINTENANCE => 'Maintenance',
                                FarmTransaction::EXPENSE_MISCELLANEOUS => 'Miscellaneous',
                            ])
                            ->default(FarmTransaction::EXPENSE_MISCELLANEOUS)
                            ->required(),

                        TextInput::make('amount')
                            ->label('Amount (৳)')
                            ->numeric()
                            ->minValue(0.01)
                            ->maxValue(9999999999.99)
                            ->step(0.01)
                            ->prefix('৳')
                            ->required(),

                        DatePicker::make('transaction_on')
                            ->label('Date')
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
                            ->default('cash')
                            ->required(),

                        TextInput::make('reference')
                            ->label('Reference')
                            ->maxLength(100),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\FarmTransactions\Schemas;

use App\Models\FarmTransaction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class FarmTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction')
                    ->description('Sales, feed and medicine usage are tracked separately and should not be re-entered here.')
                    ->columns(2)
                    ->schema([
                        Select::make('transaction_type')
                            ->label('Type')
                            ->options([
                                FarmTransaction::TYPE_INCOME => 'Other Income',
                                FarmTransaction::TYPE_EXPENSE => 'Expense',
                            ])
                            ->live()
                            ->required(),

                        Select::make('category')
                            ->label('Category')
                            ->options(fn (Get $get): array => $get('transaction_type') === FarmTransaction::TYPE_INCOME
                                ? [FarmTransaction::INCOME_OTHER => 'Other income']
                                : [
                                    FarmTransaction::EXPENSE_LABOUR => 'Labour',
                                    FarmTransaction::EXPENSE_ELECTRICITY => 'Electricity',
                                    FarmTransaction::EXPENSE_TRANSPORT => 'Transportation',
                                    FarmTransaction::EXPENSE_MAINTENANCE => 'Maintenance',
                                    FarmTransaction::EXPENSE_MISCELLANEOUS => 'Miscellaneous',
                                ])
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

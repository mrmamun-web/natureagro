<?php

namespace App\Filament\Resources\FarmTransactions\Tables;

use App\Models\FarmTransaction;
use App\Support\SafeDeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FarmTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_on')->label('Date')->date('d M Y')->sortable(),
                TextColumn::make('transaction_type')->label('Type')->badge()->color(fn (string $state): string => $state === FarmTransaction::TYPE_INCOME ? 'success' : 'danger'),
                TextColumn::make('category')->label('Category')->badge()->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString()),
                TextColumn::make('amount')->label('Amount')->money('BDT')->sortable(),
                TextColumn::make('payment_method')->label('Method')->placeholder('—'),
                TextColumn::make('reference')->label('Reference')->searchable()->placeholder('—'),
                TextColumn::make('reversedTransaction.id')->label('Reversal')->formatStateUsing(fn ($state): string => $state ? 'Reversed' : 'Original')->badge()->color(fn ($state): string => $state ? 'warning' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('transaction_type')->options([
                    FarmTransaction::TYPE_INCOME => 'Other Income',
                    FarmTransaction::TYPE_EXPENSE => 'Expense',
                ]),
                SelectFilter::make('category')->options([
                    FarmTransaction::INCOME_OTHER => 'Other income',
                    FarmTransaction::EXPENSE_LABOUR => 'Labour',
                    FarmTransaction::EXPENSE_ELECTRICITY => 'Electricity',
                    FarmTransaction::EXPENSE_TRANSPORT => 'Transportation',
                    FarmTransaction::EXPENSE_MAINTENANCE => 'Maintenance',
                    FarmTransaction::EXPENSE_MISCELLANEOUS => 'Miscellaneous',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                SafeDeleteAction::make('transaction'),
            ])
            ->defaultSort('transaction_on', 'desc');
    }
}

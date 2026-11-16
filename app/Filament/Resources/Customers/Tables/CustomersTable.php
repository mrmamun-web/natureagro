<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone_number')
                    ->label('Phone')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('sales_count')
                    ->label('Sales')
                    ->counts('sales')
                    ->sortable(),

                TextColumn::make('sales_sum_due_amount')
                    ->label('Outstanding')
                    ->sum('sales', 'due_amount')
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('name');
    }
}

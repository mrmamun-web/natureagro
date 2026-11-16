<?php

namespace App\Filament\Resources\Poultries\Tables;

use App\Models\Poultry;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PoultriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('poultry_code')
                    ->label('Poultry Code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        Poultry::TYPE_CHICKEN => 'info',
                        Poultry::TYPE_ROOSTER => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('breed')
                    ->label('Breed')
                    ->searchable()
                    ->placeholder('Not specified'),

                TextColumn::make('initial_quantity')
                    ->label('Initial')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('current_quantity')
                    ->label('Current Stock')
                    ->numeric()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('unit_purchase_price')
                    ->label('Unit Price')
                    ->money('BDT')
                    ->sortable()
                    ->placeholder('N/A'),

                TextColumn::make('purchase_date')
                    ->label('Purchase Date')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('N/A'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        Poultry::STATUS_ACTIVE => 'success',
                        Poultry::STATUS_SOLD => 'info',
                        Poultry::STATUS_DECEASED => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Poultry Type')
                    ->options([
                        Poultry::TYPE_CHICKEN => 'Chicken',
                        Poultry::TYPE_ROOSTER => 'Rooster',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        Poultry::STATUS_ACTIVE => 'Active',
                        Poultry::STATUS_SOLD => 'Sold',
                        Poultry::STATUS_DECEASED => 'Deceased',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

<?php

namespace App\Filament\Resources\EggInventoryMovements;

use App\Filament\Resources\EggInventoryMovements\Pages\ListEggInventoryMovements;
use App\Models\EggInventoryMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EggInventoryMovementResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = EggInventoryMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $navigationLabel = 'Egg Stock History';

    protected static string|\UnitEnum|null $navigationGroup = 'Flock & Eggs';

    protected static ?int $navigationSort = 35;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('parentFlock.name')
                    ->label('Parent Flock')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('movement_type')
                    ->label('Movement')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString()),

                TextColumn::make('good_delta')
                    ->label('Good Eggs (+/-)')
                    ->numeric()
                    ->color(fn (int $state): string => $state < 0 ? 'danger' : 'success'),

                TextColumn::make('damaged_delta')
                    ->label('Damaged Eggs (+/-)')
                    ->numeric()
                    ->color(fn (int $state): string => $state < 0 ? 'danger' : 'warning'),

                TextColumn::make('reserved_delta')
                    ->label('Reserved (+/-)')
                    ->numeric()
                    ->color(fn (int $state): string => $state < 0 ? 'danger' : 'info'),

                TextColumn::make('total_cost')
                    ->label('Cost moved (BDT)')
                    ->money('BDT')
                    ->placeholder('—'),

                TextColumn::make('source_type')
                    ->label('Source')
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),

                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(40)
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEggInventoryMovements::route('/'),
        ];
    }
}

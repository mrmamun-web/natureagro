<?php

namespace App\Filament\Resources\EggStocks;

use App\Filament\Resources\EggStocks\Pages\ListEggStocks;
use App\Models\EggStock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EggStockResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = EggStock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationLabel = 'Egg Inventory';

    protected static string|\UnitEnum|null $navigationGroup = 'Flock & Eggs';

    protected static ?int $navigationSort = 30;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('parentFlock.name')
                    ->label('Parent Flock')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('good_available')
                    ->label('Available Good Eggs')
                    ->numeric()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('reserved_for_hatching')
                    ->label('Reserved for Hatching')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('damaged')
                    ->label('Damaged Eggs')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEggStocks::route('/'),
        ];
    }
}

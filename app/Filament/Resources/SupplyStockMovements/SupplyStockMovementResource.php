<?php

namespace App\Filament\Resources\SupplyStockMovements;

use App\Filament\Resources\SupplyStockMovements\Pages\ListSupplyStockMovements;
use App\Models\SupplyStockMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupplyStockMovementResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = SupplyStockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $navigationLabel = 'Feed & Medicine Stock Ledger';

    protected static string|\UnitEnum|null $navigationGroup = 'Feed & Medicine';

    protected static ?int $navigationSort = 34;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('moved_on')->label('Date')->date('d M Y')->sortable(),
                TextColumn::make('product.name')->label('Product')->searchable()->sortable(),
                TextColumn::make('movement_type')->label('Activity')->badge(),
                TextColumn::make('quantity_delta')->label('Quantity change')->numeric(decimalPlaces: 3)->sortable(),
                TextColumn::make('lot.expires_on')->label('Lot expiry')->date('d M Y')->placeholder('—'),
                TextColumn::make('unit_cost')->label('Unit cost')->money('BDT')->placeholder('—'),
                TextColumn::make('total_cost')->label('Cost')->money('BDT')->placeholder('—'),
                TextColumn::make('source_type')
                    ->label('Source')
                    ->formatStateUsing(fn (?string $state, SupplyStockMovement $record): string => $state
                        ? class_basename($state).' #'.$record->source_id
                        : 'Manual'),
            ])
            ->filters([
                SelectFilter::make('movement_type')->options([
                    'purchase' => 'Purchase',
                    'usage' => 'Consumption',
                ]),
                SelectFilter::make('supply_product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable(),
            ])
            ->defaultSort('moved_on', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupplyStockMovements::route('/'),
        ];
    }
}

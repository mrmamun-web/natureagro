<?php

namespace App\Filament\Resources\SupplyProducts;

use App\Models\SupplyProduct;
use App\Support\SafeDeleteAction;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupplyProductResource extends Resource
{
    protected static ?string $model = SupplyProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;


    protected static bool $shouldRegisterNavigation = false;


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Inventory product')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(150),
                Select::make('type')
                    ->required()
                    ->disabled(fn (?SupplyProduct $record): bool => $record !== null && ($record->purchases()->exists() || $record->usages()->exists()))
                    ->dehydrated(fn (?SupplyProduct $record): bool => $record === null || (! $record->purchases()->exists() && ! $record->usages()->exists()))
                    ->options([
                        SupplyProduct::TYPE_FEED => 'Feed',
                        SupplyProduct::TYPE_MEDICINE => 'Medicine',
                    ]),
                TextInput::make('unit')
                    ->label('Stock unit')
                    ->placeholder('kg, bag, bottle')
                    ->disabled(fn (?SupplyProduct $record): bool => $record !== null && $record->purchases()->exists())
                    ->dehydrated(fn (?SupplyProduct $record): bool => $record === null || ! $record->purchases()->exists())
                    ->required()
                    ->maxLength(30),
                TextInput::make('minimum_stock')->label('Low stock alert at')->numeric()->minValue(0)->step(0.001)->default(0)->required(),
                Toggle::make('is_active')->default(true),
                Textarea::make('description')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('type')->badge()->formatStateUsing(fn (string $state): string => ucfirst($state))->sortable(),
            TextColumn::make('unit')->label('Unit'),
            TextColumn::make('lots_sum_remaining_quantity')->sum('lots', 'remaining_quantity')->label('On hand')->numeric(decimalPlaces: 3)->sortable(),
            TextColumn::make('minimum_stock')->label('Alert threshold')->numeric(decimalPlaces: 3),
            TextColumn::make('stock_alert')
                ->label('Stock alert')
                ->state(fn (SupplyProduct $record): string => (float) $record->minimum_stock > 0 && $record->usable_stock_quantity <= (float) $record->minimum_stock ? 'Low' : 'OK')
                ->badge()
                ->color(fn (string $state): string => $state === 'Low' ? 'danger' : 'success'),
            IconColumn::make('is_active')->boolean()->label('Active'),
        ])->filters([
            SelectFilter::make('type')->options([
                SupplyProduct::TYPE_FEED => 'Feed',
                SupplyProduct::TYPE_MEDICINE => 'Medicine',
            ]),
        ])->recordActions([
            EditAction::make(),
            SafeDeleteAction::make('feed or medicine product'),
        ])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplyProducts::route('/'),
            'create' => Pages\CreateSupplyProduct::route('/create'),
            'edit' => Pages\EditSupplyProduct::route('/{record}/edit'),
        ];
    }
}

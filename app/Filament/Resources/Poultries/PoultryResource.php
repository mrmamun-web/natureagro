<?php

namespace App\Filament\Resources\Poultries;

use App\Filament\Resources\Poultries\Pages\CreatePoultry;
use App\Filament\Resources\Poultries\Pages\EditPoultry;
use App\Filament\Resources\Poultries\Pages\ListPoultries;
use App\Filament\Resources\Poultries\Pages\ViewPoultry;
use App\Filament\Resources\Poultries\RelationManagers\MovementsRelationManager;
use App\Filament\Resources\Poultries\Schemas\PoultryForm;
use App\Filament\Resources\Poultries\Schemas\PoultryInfolist;
use App\Filament\Resources\Poultries\Tables\PoultriesTable;
use App\Models\Poultry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PoultryResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Poultry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Birds for Sale';

    protected static string|\UnitEnum|null $navigationGroup = 'Sales & Customers';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'poultry_code';

    public static function form(Schema $schema): Schema
    {
        return PoultryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PoultryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PoultriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPoultries::route('/'),
            'create' => CreatePoultry::route('/create'),
            'view' => ViewPoultry::route('/{record}'),
            'edit' => EditPoultry::route('/{record}/edit'),
        ];
    }
}

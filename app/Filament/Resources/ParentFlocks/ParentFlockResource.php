<?php

namespace App\Filament\Resources\ParentFlocks;

use App\Filament\Resources\ParentFlocks\Pages\CreateParentFlock;
use App\Filament\Resources\ParentFlocks\Pages\EditParentFlock;
use App\Filament\Resources\ParentFlocks\Pages\ListParentFlocks;
use App\Filament\Resources\ParentFlocks\Pages\ViewParentFlock;
use App\Filament\Resources\ParentFlocks\RelationManagers\MovementsRelationManager;
use App\Filament\Resources\ParentFlocks\Schemas\ParentFlockForm;
use App\Filament\Resources\ParentFlocks\Schemas\ParentFlockInfolist;
use App\Filament\Resources\ParentFlocks\Tables\ParentFlocksTable;
use App\Models\ParentFlock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ParentFlockResource extends Resource
{
    protected static ?string $model = ParentFlock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Parent Flocks';

    protected static string|\UnitEnum|null $navigationGroup = 'Flock & Eggs';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ParentFlockForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ParentFlockInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParentFlocksTable::configure($table);
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
            'index' => ListParentFlocks::route('/'),
            'create' => CreateParentFlock::route('/create'),
            'view' => ViewParentFlock::route('/{record}'),
            'edit' => EditParentFlock::route('/{record}/edit'),
        ];
    }
}

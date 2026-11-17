<?php

namespace App\Filament\Resources\ChickBatches;

use App\Filament\Resources\ChickBatches\Pages\ListChickBatches;
use App\Filament\Resources\ChickBatches\Pages\ViewChickBatch;
use App\Filament\Resources\ChickBatches\RelationManagers\MovementsRelationManager;
use App\Filament\Resources\ChickBatches\Schemas\ChickBatchForm;
use App\Filament\Resources\ChickBatches\Schemas\ChickBatchInfolist;
use App\Filament\Resources\ChickBatches\Tables\ChickBatchesTable;
use App\Models\ChickBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ChickBatchResource extends Resource
{
    protected static ?string $model = ChickBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Chick Batches';

    protected static string|\UnitEnum|null $navigationGroup = 'Hatching & Chicks';

    protected static ?int $navigationSort = 50;

    protected static ?string $recordTitleAttribute = 'batch_code';

    public static function form(Schema $schema): Schema
    {
        return ChickBatchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ChickBatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChickBatchesTable::configure($table);
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
            'index' => ListChickBatches::route('/'),
            'view' => ViewChickBatch::route('/{record}'),
        ];
    }
}

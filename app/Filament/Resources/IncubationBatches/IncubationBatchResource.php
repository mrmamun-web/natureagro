<?php

namespace App\Filament\Resources\IncubationBatches;

use App\Filament\Resources\IncubationBatches\Pages\CreateIncubationBatch;
use App\Filament\Resources\IncubationBatches\Pages\ListIncubationBatches;
use App\Filament\Resources\IncubationBatches\Pages\ViewIncubationBatch;
use App\Filament\Resources\IncubationBatches\Schemas\IncubationBatchForm;
use App\Filament\Resources\IncubationBatches\Schemas\IncubationBatchInfolist;
use App\Filament\Resources\IncubationBatches\Tables\IncubationBatchesTable;
use App\Models\IncubationBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IncubationBatchResource extends Resource
{
    protected static ?string $model = IncubationBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSun;

    protected static ?string $navigationLabel = 'Incubation & Hatching';

    protected static string|\UnitEnum|null $navigationGroup = 'Hatching & Chicks';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'batch_code';

    public static function form(Schema $schema): Schema
    {
        return IncubationBatchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return IncubationBatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IncubationBatchesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIncubationBatches::route('/'),
            'create' => CreateIncubationBatch::route('/create'),
            'view' => ViewIncubationBatch::route('/{record}'),
        ];
    }
}

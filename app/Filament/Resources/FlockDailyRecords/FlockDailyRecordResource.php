<?php

namespace App\Filament\Resources\FlockDailyRecords;

use App\Filament\Resources\FlockDailyRecords\Pages\CreateFlockDailyRecord;
use App\Filament\Resources\FlockDailyRecords\Pages\ListFlockDailyRecords;
use App\Filament\Resources\FlockDailyRecords\Pages\ViewFlockDailyRecord;
use App\Filament\Resources\FlockDailyRecords\Schemas\FlockDailyRecordForm;
use App\Filament\Resources\FlockDailyRecords\Schemas\FlockDailyRecordInfolist;
use App\Filament\Resources\FlockDailyRecords\Tables\FlockDailyRecordsTable;
use App\Models\FlockDailyRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FlockDailyRecordResource extends Resource
{
    protected static ?string $model = FlockDailyRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Daily Flock & Egg Update';

    protected static string|\UnitEnum|null $navigationGroup = 'Flock & Eggs';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'recorded_on';

    public static function form(Schema $schema): Schema
    {
        return FlockDailyRecordForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FlockDailyRecordInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FlockDailyRecordsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFlockDailyRecords::route('/'),
            'create' => CreateFlockDailyRecord::route('/create'),
            'view' => ViewFlockDailyRecord::route('/{record}'),
        ];
    }
}

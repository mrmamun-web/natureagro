<?php

namespace App\Filament\Resources\FlockDailyRecords\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FlockDailyRecordInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Daily flock record')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('parentFlock.name')->label('Parent Flock'),
                        TextEntry::make('recorded_on')->label('Record Date')->date('d M Y'),
                        TextEntry::make('male_mortality')->label('Male Mortality')->numeric(),
                        TextEntry::make('female_mortality')->label('Female Mortality')->numeric(),
                        TextEntry::make('good_eggs')->label('Good Eggs')->numeric(),
                        TextEntry::make('damaged_eggs')->label('Damaged Eggs')->numeric(),
                        TextEntry::make('feed_consumed_kg')->label('Feed Consumed')->suffix(' kg'),
                        TextEntry::make('health_status')->label('Health Status')->badge(),
                        TextEntry::make('notes')->label('Notes')->placeholder('No additional notes')->columnSpanFull(),
                    ]),
            ]);
    }
}

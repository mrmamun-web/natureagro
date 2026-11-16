<?php

namespace App\Filament\Resources\IncubationBatches\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IncubationBatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Incubation')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('batch_code')->label('Batch Code')->copyable(),
                        TextEntry::make('parentFlock.name')->label('Egg Source Flock'),
                        TextEntry::make('eggs_set')->label('Eggs Set')->numeric(),
                        TextEntry::make('incubated_on')->label('Incubation Date')->date('d M Y'),
                        TextEntry::make('expected_hatch_on')->label('Expected Hatch Date')->date('d M Y'),
                        TextEntry::make('status')->label('Status')->badge(),
                    ]),

                Section::make('Hatch result')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('actual_hatch_on')->label('Actual Hatch Date')->date('d M Y')->placeholder('Not completed'),
                        TextEntry::make('hatched_chicks')->label('Hatched Chicks')->numeric(),
                        TextEntry::make('failed_eggs')->label('Failed Eggs')->numeric(),
                        TextEntry::make('hatch_rate')
                            ->label('Hatch Success Rate')
                            ->suffix('%')
                            ->placeholder('Pending'),
                        TextEntry::make('chickBatch.batch_code')->label('Generated Chick Batch')->placeholder('No chicks hatched'),
                        TextEntry::make('notes')->label('Notes')->placeholder('No additional notes')->columnSpanFull(),
                    ]),
            ]);
    }
}

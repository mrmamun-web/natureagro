<?php

namespace App\Filament\Resources\ChickBatches\Schemas;

use App\Models\ChickBatch;
use App\Models\ChickBatchMovement;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ChickBatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Batch details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('batch_code')->label('Batch Code')->copyable(),
                        TextEntry::make('name')->label('Batch Name'),
                        TextEntry::make('breed')->label('Breed')->placeholder('Not specified'),
                        TextEntry::make('source')->label('Source')->badge(),
                        TextEntry::make('sourceIncubationBatch.batch_code')->label('Hatching Batch')->placeholder('No linked hatch record'),
                        TextEntry::make('hatch_date')->label('Hatch Date')->date('d M Y'),
                        TextEntry::make('batch_month_name')->label('Batch Month'),
                    ]),

                Section::make('Current stock')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('initial_quantity')->label('Chicks Hatched')->numeric(),
                        TextEntry::make('mortality_total')
                            ->label('Deaths')
                            ->state(fn (ChickBatch $record): int => (int) $record->movements()
                                ->where('movement_type', ChickBatchMovement::TYPE_MORTALITY)
                                ->sum('quantity')),
                        TextEntry::make('current_quantity')->label('Chicks Alive Now')->numeric(),
                        TextEntry::make('age_months')->label('Age')->suffix(' months'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('notes')->label('Notes')->placeholder('No additional notes')->columnSpanFull(),
                    ]),
            ]);
    }
}

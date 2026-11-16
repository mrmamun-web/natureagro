<?php

namespace App\Filament\Resources\ChickBatches\Schemas;

use App\Models\ChickBatch;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ChickBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Purchased chick batch')
                    ->description('Hatched batches are created automatically when the incubation outcome is recorded.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Batch Name')
                            ->required()
                            ->maxLength(150),

                        TextInput::make('breed')
                            ->label('Breed')
                            ->maxLength(100),

                        DatePicker::make('hatch_date')
                            ->label('Hatch / Purchase Date')
                            ->default(now()->toDateString())
                            ->maxDate(now())
                            ->native(false)
                            ->required(),

                        TextInput::make('initial_quantity')
                            ->label('Initial Chicks')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required(),

                        TextInput::make('initial_unit_cost')
                            ->label('Purchase cost per chick')
                            ->numeric()
                            ->prefix('৳')
                            ->minValue(0)
                            ->required(),

                        Hidden::make('source')
                            ->default(ChickBatch::SOURCE_PURCHASED)
                            ->required(),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}

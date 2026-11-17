<?php

namespace App\Filament\Resources\ChickBatches\Schemas;

use App\Models\ChickBatch;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ChickBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Hatched chick batch')
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
                            ->label('Hatch Date')
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

                        Hidden::make('source')
                            ->default(ChickBatch::SOURCE_HATCHED)
                            ->required(),

                    ])
                    ->columns(2),
            ]);
    }
}

<?php

namespace App\Filament\Resources\IncubationBatches\Schemas;

use App\Models\ParentFlock;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class IncubationBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Incubation details')
                    ->description('Starting incubation reserves good eggs from the selected flock.')
                    ->schema([
                        Select::make('parent_flock_id')
                            ->label('Egg Source Flock')
                            ->options(fn (): array => ParentFlock::query()
                                ->active()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),

                        TextInput::make('eggs_set')
                            ->label('Eggs Set')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required(),

                        DatePicker::make('incubated_on')
                            ->label('Incubation Date')
                            ->default(now()->toDateString())
                            ->maxDate(now())
                            ->native(false)
                            ->required(),

                        DatePicker::make('expected_hatch_on')
                            ->label('Expected Hatch Date')
                            ->default(now()->addDays(21)->toDateString())
                            ->minDate(fn (Get $get) => $get('incubated_on'))
                            ->native(false)
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

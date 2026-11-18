<?php

namespace App\Filament\Resources\IncubationBatches\Schemas;

use App\Models\IncubationBatch;
use App\Models\ParentFlock;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

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
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if (blank($state)) {
                                    return;
                                }

                                $incubatedOn = Carbon::parse($state);

                                $set('incubation_month', $incubatedOn->month);

                                $set(
                                    'expected_hatch_on',
                                    $incubatedOn->copy()
                                        ->addDays(IncubationBatch::HATCHING_PERIOD_DAYS)
                                        ->toDateString()
                                );
                            })
                            ->required(),

                        Select::make('incubation_month')
                            ->label('Incubation Month')
                            ->options(IncubationBatch::monthOptions())
                            ->default(now()->month)
                            ->disabled()
                            ->dehydrated()
                            ->rules(['integer', 'between:1,12'])
                            ->required(),

                        DatePicker::make('expected_hatch_on')
                            ->label('Expected Hatch Date')
                            ->minDate(fn (Get $get) => $get('incubated_on')
                                ? Carbon::parse($get('incubated_on'))
                                    ->addDays(IncubationBatch::HATCHING_PERIOD_DAYS)
                                    ->toDateString()
                                : null)
                            ->native(false)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}

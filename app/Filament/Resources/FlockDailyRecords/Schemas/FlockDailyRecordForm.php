<?php

namespace App\Filament\Resources\FlockDailyRecords\Schemas;

use App\Models\ParentFlock;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class FlockDailyRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Daily egg and flock update')
                    ->description("Record the day's egg count and any parent birds lost.")
                    ->schema([
                        Select::make('parent_flock_id')
                            ->label('Parent flock')
                            ->options(fn (): array => ParentFlock::query()
                                ->active()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),

                        DatePicker::make('recorded_on')
                            ->label('Date')
                            ->default(now()->toDateString())
                            ->maxDate(now())
                            ->native(false)
                            ->required(),

                        TextInput::make('male_mortality')
                            ->label('Male birds lost')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required(),

                        TextInput::make('female_mortality')
                            ->label('Female birds lost')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required(),

                        TextInput::make('good_eggs')
                            ->label('Good eggs')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->live()
                            ->required(),

                        TextInput::make('damaged_eggs')
                            ->label('Damaged eggs')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->live()
                            ->required(),

                        Placeholder::make('total_eggs_preview')
                            ->label('Total eggs collected')
                            ->content(fn (Get $get): string => number_format(
                                (int) $get('good_eggs') + (int) $get('damaged_eggs'),
                            ))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}

<?php

namespace App\Filament\Resources\ParentFlocks\Schemas;

use App\Models\ParentFlock;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ParentFlockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('health_status')
                    ->default(ParentFlock::HEALTHY)
                    ->required(),

                Section::make('Flock identity')
                    ->description('Register a breeding flock and its initial population.')
                    ->schema([
                        Hidden::make('flock_code')
                            ->default(fn (): string => 'PFL-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)))
                            ->required()
                            ->unique(ignoreRecord: true),

                        TextInput::make('name')
                            ->label('Flock Name')
                            ->required()
                            ->maxLength(150),

                        TextInput::make('breed')
                            ->label('Breed')
                            ->default('Deshi')
                            ->maxLength(100),
                    ])
                    ->columns(2),

                Section::make('Initial flock population')
                    ->description('Edit the opening male and female counts; current stock adjusts by the same amount.')
                    ->schema([
                        TextInput::make('initial_male_quantity')
                            ->label('Initial Males')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->required(),

                        TextInput::make('initial_female_quantity')
                            ->label('Initial Females')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->required(),

                        TextInput::make('current_male_quantity')
                            ->label('Current Males')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?ParentFlock $record): bool => $record !== null),

                        TextInput::make('current_female_quantity')
                            ->label('Current Females')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?ParentFlock $record): bool => $record !== null),
                    ])
                    ->columns(2),

                Section::make('Flock status')
                    ->schema([
                        Select::make('status')
                            ->label('Flock Status')
                            ->options([
                                ParentFlock::STATUS_ACTIVE => 'Active',
                                ParentFlock::STATUS_INACTIVE => 'Inactive',
                            ])
                            ->default(ParentFlock::STATUS_ACTIVE)
                            ->required(),
                    ]),
            ]);
    }
}

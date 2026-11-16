<?php

namespace App\Filament\Resources\ParentFlocks\Schemas;

use App\Models\ParentFlock;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ParentFlockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Flock identity')
                    ->description('Register a breeding flock and its initial population.')
                    ->schema([
                        TextInput::make('flock_code')
                            ->label('Flock Code')
                            ->default(fn (): string => 'PFL-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        TextInput::make('name')
                            ->label('Flock Name')
                            ->required()
                            ->maxLength(150),

                        TextInput::make('breed')
                            ->label('Breed')
                            ->default('Deshi')
                            ->maxLength(100),

                        DatePicker::make('purchase_date')
                            ->label('Purchase Date')
                            ->native(false),
                    ])
                    ->columns(2),

                Section::make('Initial flock population')
                    ->description('Initial counts are preserved; daily mortality records update the current stock.')
                    ->schema([
                        TextInput::make('initial_male_quantity')
                            ->label('Initial Males')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->required()
                            ->disabled(fn (?ParentFlock $record): bool => $record !== null),

                        TextInput::make('initial_female_quantity')
                            ->label('Initial Females')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->required()
                            ->disabled(fn (?ParentFlock $record): bool => $record !== null),

                        TextInput::make('initial_male_unit_cost')
                            ->label('Male acquisition cost per bird')
                            ->numeric()
                            ->prefix('৳')
                            ->minValue(0)
                            ->required(fn (Get $get): bool => (int) $get('initial_male_quantity') > 0)
                            ->visible(fn (?ParentFlock $record): bool => $record === null),

                        TextInput::make('initial_female_unit_cost')
                            ->label('Female acquisition cost per bird')
                            ->numeric()
                            ->prefix('৳')
                            ->minValue(0)
                            ->required(fn (Get $get): bool => (int) $get('initial_female_quantity') > 0)
                            ->visible(fn (?ParentFlock $record): bool => $record === null),

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

                Section::make('Health & status')
                    ->schema([
                        Select::make('health_status')
                            ->label('Health Status')
                            ->options([
                                ParentFlock::HEALTHY => 'Healthy',
                                ParentFlock::HEALTH_MONITORING => 'Needs Monitoring',
                                ParentFlock::HEALTH_CRITICAL => 'Critical',
                            ])
                            ->default(ParentFlock::HEALTHY)
                            ->required(),

                        Select::make('status')
                            ->label('Flock Status')
                            ->options([
                                ParentFlock::STATUS_ACTIVE => 'Active',
                                ParentFlock::STATUS_INACTIVE => 'Inactive',
                            ])
                            ->default(ParentFlock::STATUS_ACTIVE)
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

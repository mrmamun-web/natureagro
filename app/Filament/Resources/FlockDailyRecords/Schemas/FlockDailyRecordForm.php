<?php

namespace App\Filament\Resources\FlockDailyRecords\Schemas;

use App\Models\ParentFlock;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FlockDailyRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Flock & record date')
                    ->schema([
                        Select::make('parent_flock_id')
                            ->label('Parent Flock')
                            ->options(fn (): array => ParentFlock::query()
                                ->active()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),

                        DatePicker::make('recorded_on')
                            ->label('Record Date')
                            ->default(now()->toDateString())
                            ->maxDate(now())
                            ->native(false)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Daily production & mortality')
                    ->description('Mortality is deducted from the flock stock when this record is saved.')
                    ->schema([
                        TextInput::make('male_mortality')
                            ->label('Male Mortality')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required(),

                        TextInput::make('female_mortality')
                            ->label('Female Mortality')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required(),

                        TextInput::make('good_eggs')
                            ->label('Good Eggs')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required(),

                        TextInput::make('damaged_eggs')
                            ->label('Damaged Eggs')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required(),

                        TextInput::make('feed_consumed_kg')
                            ->label('Feed Consumed (kg)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Health & notes')
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

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}

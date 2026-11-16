<?php

namespace App\Filament\Resources\Poultries\Schemas;

use App\Models\Poultry;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PoultryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Poultry Information')
                    ->schema([
                        TextInput::make('poultry_code')
                            ->label('Poultry Code')
                            ->default(fn () => 'POU-'.now()->format('Ymd').'-'.strtoupper(Str::random(5)))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(150),

                        Select::make('type')
                            ->label('Poultry Type')
                            ->options([
                                Poultry::TYPE_CHICKEN => 'Chicken',
                                Poultry::TYPE_ROOSTER => 'Rooster',
                            ])
                            ->disabled(fn (?Poultry $record): bool => $record !== null)
                            ->required(),

                        TextInput::make('breed')
                            ->label('Breed')
                            ->maxLength(100),
                    ])
                    ->columns(2),

                Section::make('Quantity')
                    ->schema([
                        TextInput::make('initial_quantity')
                            ->label('Initial Quantity')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->disabled(fn (?Poultry $record): bool => $record !== null)
                            ->required(),

                        TextInput::make('current_quantity')
                            ->label('Current Quantity')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?Poultry $record): bool => $record !== null),
                    ])
                    ->columns(2),

                Section::make('Purchase & Age')
                    ->schema([
                        DatePicker::make('purchase_date')
                            ->label('Purchase Date')
                            ->native(false),

                        TextInput::make('unit_purchase_price')
                            ->label('Unit Purchase Price')
                            ->numeric()
                            ->prefix('৳')
                            ->minValue(0)
                            ->required(fn (Get $get): bool => (int) $get('initial_quantity') > 0)
                            ->disabled(fn (?Poultry $record): bool => $record !== null)
                            ->visible(fn (?Poultry $record): bool => $record === null),

                        DatePicker::make('date_of_birth')
                            ->label('Date of Birth')
                            ->native(false),
                    ])
                    ->columns(3),

                Section::make('Status & Notes')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                Poultry::STATUS_ACTIVE => 'Active',
                                Poultry::STATUS_SOLD => 'Sold',
                                Poultry::STATUS_DECEASED => 'Deceased',
                            ])
                            ->default(Poultry::STATUS_ACTIVE)
                            ->disabled(fn (?Poultry $record): bool => $record !== null)
                            ->dehydrated(fn (?Poultry $record): bool => $record === null)
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

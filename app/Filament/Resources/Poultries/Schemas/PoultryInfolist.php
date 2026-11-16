<?php

namespace App\Filament\Resources\Poultries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PoultryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Poultry Identification')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('poultry_code')
                            ->label('Poultry Code')
                            ->copyable(),

                        TextEntry::make('name')
                            ->label('Name'),

                        TextEntry::make('type')
                            ->label('Type')
                            ->badge(),

                        TextEntry::make('breed')
                            ->label('Breed')
                            ->placeholder('Not specified'),
                    ]),

                Section::make('Quantity Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('initial_quantity')
                            ->label('Initial Quantity')
                            ->numeric(),

                        TextEntry::make('current_quantity')
                            ->label('Current Quantity')
                            ->numeric(),
                    ]),

                Section::make('Purchase & Age Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('purchase_date')
                            ->label('Purchase Date')
                            ->date('d M Y')
                            ->placeholder('Not specified'),

                        TextEntry::make('unit_purchase_price')
                            ->label('Purchase Price Per Bird')
                            ->money('BDT')
                            ->placeholder('Not specified'),

                        TextEntry::make('date_of_birth')
                            ->label('Date of Birth')
                            ->date('d M Y')
                            ->placeholder('Not specified'),
                    ]),

                Section::make('Status & Additional Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),

                        TextEntry::make('notes')
                            ->label('Notes')
                            ->placeholder('No additional notes')
                            ->columnSpanFull(),

                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ]),
            ]);
    }
}

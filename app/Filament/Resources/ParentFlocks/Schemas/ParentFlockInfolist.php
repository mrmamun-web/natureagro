<?php

namespace App\Filament\Resources\ParentFlocks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ParentFlockInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Flock details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('flock_code')->label('Flock Code')->copyable(),
                        TextEntry::make('name')->label('Flock Name'),
                        TextEntry::make('breed')->label('Breed')->placeholder('Not specified'),
                        TextEntry::make('purchase_date')->label('Purchase Date')->date('d M Y')->placeholder('Not specified'),
                    ]),

                Section::make('Population')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('initial_male_quantity')->label('Initial Males')->numeric(),
                        TextEntry::make('initial_female_quantity')->label('Initial Females')->numeric(),
                        TextEntry::make('current_male_quantity')->label('Current Males')->numeric(),
                        TextEntry::make('current_female_quantity')->label('Current Females')->numeric(),
                    ]),

                Section::make('Health & notes')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('health_status')->label('Health Status')->badge(),
                        TextEntry::make('status')->label('Flock Status')->badge(),
                        TextEntry::make('notes')->label('Notes')->placeholder('No additional notes')->columnSpanFull(),
                        TextEntry::make('created_at')->label('Created')->dateTime(),
                        TextEntry::make('updated_at')->label('Last Updated')->dateTime(),
                    ]),
            ]);
    }
}

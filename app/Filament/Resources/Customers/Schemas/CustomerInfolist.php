<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer profile')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('Name'),
                        TextEntry::make('phone_number')->label('Phone')->placeholder('Not provided'),
                        TextEntry::make('address')->label('Address')->placeholder('Not provided')->columnSpanFull(),
                        TextEntry::make('notes')->label('Notes')->placeholder('No additional notes')->columnSpanFull(),
                        TextEntry::make('sales_count')->label('Recorded Sales')->state(fn ($record): int => $record->sales()->count()),
                        TextEntry::make('outstanding_due')->label('Outstanding Due')->state(fn ($record): string => '৳ '.number_format((float) $record->sales()->sum('due_amount'), 2)),
                    ]),
            ]);
    }
}

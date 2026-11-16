<?php

namespace App\Filament\Resources\Sales\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Payment history';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paid_on')->label('Date')->date('d M Y')->sortable(),
                TextColumn::make('amount')->label('Amount')->money('BDT')->sortable(),
                TextColumn::make('payment_method')->label('Method')->badge(),
                TextColumn::make('reference')->label('Reference')->placeholder('—'),
                TextColumn::make('notes')->label('Notes')->limit(40)->placeholder('—'),
            ]);
    }
}

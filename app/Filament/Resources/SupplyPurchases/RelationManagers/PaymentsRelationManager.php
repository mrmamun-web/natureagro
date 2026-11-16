<?php

namespace App\Filament\Resources\SupplyPurchases\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Supplier payment history';

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('paid_on')->date('d M Y')->sortable(),
            TextColumn::make('amount')->money('BDT')->sortable(),
            TextColumn::make('payment_method')->badge(),
            TextColumn::make('reference')->placeholder('—'),
            TextColumn::make('notes')->limit(40)->placeholder('—'),
        ]);
    }
}

<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesRelationManager extends RelationManager
{
    protected static string $relationship = 'sales';

    protected static ?string $title = 'Sales history and balances';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->label('Invoice')->searchable()->copyable(),
                TextColumn::make('sold_on')->label('Date')->date('d M Y')->sortable(),
                TextColumn::make('product_type')->label('Product')->badge(),
                TextColumn::make('quantity')->label('Quantity')->numeric(),
                TextColumn::make('total_amount')->label('Total')->money('BDT'),
                TextColumn::make('paid_amount')->label('Paid')->money('BDT'),
                TextColumn::make('due_amount')->label('Due')->money('BDT'),
                TextColumn::make('payment_status')->label('Payment')->badge(),
            ])
            ->defaultSort('sold_on', 'desc');
    }
}

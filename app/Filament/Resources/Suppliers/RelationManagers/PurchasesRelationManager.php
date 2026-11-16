<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchasesRelationManager extends RelationManager
{
    protected static string $relationship = 'purchases';

    protected static ?string $title = 'Purchase and payable history';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('purchase_number')->label('Purchase')->searchable()->copyable(),
                TextColumn::make('purchased_on')->label('Date')->date('d M Y')->sortable(),
                TextColumn::make('product.name')->label('Product')->searchable(),
                TextColumn::make('total_amount')->label('Total')->money('BDT'),
                TextColumn::make('paid_amount')->label('Paid')->money('BDT'),
                TextColumn::make('due_amount')->label('Due')->money('BDT'),
                TextColumn::make('payment_status')->label('Payment')->badge(),
            ])
            ->defaultSort('purchased_on', 'desc');
    }
}

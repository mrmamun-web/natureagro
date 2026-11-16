<?php

namespace App\Filament\Resources\SalePayments;

use App\Filament\Resources\SalePayments\Pages\ListSalePayments;
use App\Models\SalePayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalePaymentResource extends Resource
{
    protected static ?string $model = SalePayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Payment Ledger';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paid_on')->label('Date')->date('d M Y')->sortable(),
                TextColumn::make('sale.invoice_number')->label('Invoice')->searchable()->sortable(),
                TextColumn::make('sale.customer.name')->label('Customer')->searchable()->placeholder('Walk-in'),
                TextColumn::make('amount')->label('Amount')->money('BDT')->sortable(),
                TextColumn::make('payment_method')->label('Method')->badge(),
                TextColumn::make('reference')->label('Reference')->placeholder('—'),
            ])
            ->defaultSort('paid_on', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalePayments::route('/'),
        ];
    }
}

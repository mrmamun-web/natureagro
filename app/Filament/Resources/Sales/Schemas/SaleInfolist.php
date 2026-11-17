<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Models\Sale;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SaleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('invoice_number')->label('Invoice')->copyable(),
                        TextEntry::make('sold_on')->label('Sale Date')->date('d M Y'),
                        TextEntry::make('customer.name')->label('Customer')->placeholder('Walk-in customer'),
                        TextEntry::make('product_type')
                            ->label('Product')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                Sale::PRODUCT_EGG => 'Eggs',
                                Sale::PRODUCT_CHICK => 'Hatch-batch chickens',
                                default => str($state)->replace('_', ' ')->title()->toString(),
                            }),
                        TextEntry::make('parentFlock.name')
                            ->label('Parent flock')
                            ->visible(fn (Sale $record): bool => in_array($record->product_type, [
                                Sale::PRODUCT_EGG,
                                Sale::PRODUCT_PARENT_HEN,
                                Sale::PRODUCT_PARENT_ROOSTER,
                            ], true)),
                        TextEntry::make('poultry.name')
                            ->label('Poultry record')
                            ->visible(fn (Sale $record): bool => $record->product_type === Sale::PRODUCT_POULTRY),
                        TextEntry::make('chickBatch.batch_code')
                            ->label('Chick batch')
                            ->visible(fn (Sale $record): bool => $record->product_type === Sale::PRODUCT_CHICK),
                        TextEntry::make('quantity')->label('Quantity')->numeric(),
                        TextEntry::make('unit_weight_kg')->label('Weight per Bird')->suffix(' kg')->placeholder('Not weight-based'),
                    ]),

                Section::make('Payment summary')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('unit_price')->label('Unit Price')->money('BDT'),
                        TextEntry::make('discount_amount')->label('Discount')->money('BDT'),
                        TextEntry::make('total_amount')->label('Invoice Total')->money('BDT')->weight('bold'),
                        TextEntry::make('paid_amount')->label('Paid')->money('BDT'),
                        TextEntry::make('due_amount')->label('Outstanding Due')->money('BDT'),
                        TextEntry::make('payment_status')->label('Payment Status')->badge(),
                        TextEntry::make('notes')->label('Notes')->placeholder('No additional notes')->columnSpanFull(),
                    ]),
            ]);
    }
}

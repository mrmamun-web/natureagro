<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Models\Sale;
use App\Support\SafeDeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'parentFlock',
                'poultry',
                'chickBatch',
            ]))
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('sold_on')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->placeholder('Walk-in'),

                TextColumn::make('product_type')
                    ->label('Product')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Sale::PRODUCT_EGG => 'Eggs',
                        Sale::PRODUCT_CHICK => 'Hatch-batch chickens',
                        default => str($state)->replace('_', ' ')->title()->toString(),
                    }),

                TextColumn::make('stock_source')
                    ->label('Stock Source')
                    ->state(fn (Sale $record): ?string => match ($record->product_type) {
                        Sale::PRODUCT_EGG,
                        Sale::PRODUCT_PARENT_HEN,
                        Sale::PRODUCT_PARENT_ROOSTER => $record->parentFlock?->name,
                        Sale::PRODUCT_POULTRY => $record->poultry?->name,
                        Sale::PRODUCT_CHICK => $record->chickBatch?->batch_code,
                        default => null,
                    })
                    ->toggleable(),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->money('BDT'),

                TextColumn::make('due_amount')
                    ->label('Due')
                    ->money('BDT')
                    ->color(fn (string $state): string => (float) $state > 0 ? 'danger' : 'success')
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Sale::PAYMENT_PAID => 'success',
                        Sale::PAYMENT_PARTIAL => 'warning',
                        default => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('product_type')
                    ->label('Product')
                    ->options([
                        Sale::PRODUCT_EGG => 'Eggs',
                        Sale::PRODUCT_CHICK => 'Hatch-batch chickens',
                    ]),

                SelectFilter::make('payment_status')
                    ->options([
                        Sale::PAYMENT_PAID => 'Paid',
                        Sale::PAYMENT_PARTIAL => 'Partially Paid',
                        Sale::PAYMENT_DUE => 'Due',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                SafeDeleteAction::make('sale'),
            ])
            ->defaultSort('sold_on', 'desc');
    }
}

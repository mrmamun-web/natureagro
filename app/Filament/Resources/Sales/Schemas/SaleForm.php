<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Models\ChickBatch;
use App\Models\Customer;
use App\Models\ParentFlock;
use App\Models\Poultry;
use App\Models\Sale;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice')
                    ->schema([
                        Select::make('customer_id')
                            ->label('Customer')
                            ->options(fn (): array => Customer::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->placeholder('Walk-in customer'),

                        DatePicker::make('sold_on')
                            ->label('Sale Date')
                            ->default(now()->toDateString())
                            ->maxDate(now())
                            ->native(false)
                            ->required(),

                        Select::make('product_type')
                            ->label('Product')
                            ->options([
                                Sale::PRODUCT_EGG => 'Eggs',
                                Sale::PRODUCT_PARENT_HEN => 'Parent Hens',
                                Sale::PRODUCT_PARENT_ROOSTER => 'Parent Roosters',
                                Sale::PRODUCT_POULTRY => 'Poultry Inventory',
                                Sale::PRODUCT_CHICK => 'Chicks',
                            ])
                            ->live()
                            ->required(),

                        Select::make('parent_flock_id')
                            ->label('Parent Flock / Egg Source')
                            ->options(fn (): array => ParentFlock::query()->active()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->visible(fn (Get $get): bool => in_array($get('product_type'), [
                                Sale::PRODUCT_EGG,
                                Sale::PRODUCT_PARENT_HEN,
                                Sale::PRODUCT_PARENT_ROOSTER,
                            ], true))
                            ->required(fn (Get $get): bool => in_array($get('product_type'), [
                                Sale::PRODUCT_EGG,
                                Sale::PRODUCT_PARENT_HEN,
                                Sale::PRODUCT_PARENT_ROOSTER,
                            ], true)),

                        Select::make('poultry_id')
                            ->label('Poultry Record')
                            ->options(fn (): array => Poultry::query()
                                ->active()
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Poultry $poultry): array => [
                                    $poultry->id => $poultry->name.' ('.ucfirst($poultry->type).', '.$poultry->current_quantity.' available)',
                                ])
                                ->all())
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('product_type') === Sale::PRODUCT_POULTRY)
                            ->required(fn (Get $get): bool => $get('product_type') === Sale::PRODUCT_POULTRY),

                        Select::make('chick_batch_id')
                            ->label('Chick Batch')
                            ->options(fn (): array => ChickBatch::query()
                                ->where('status', ChickBatch::STATUS_ACTIVE)
                                ->orderBy('hatch_date')
                                ->get()
                                ->mapWithKeys(fn (ChickBatch $batch): array => [
                                    $batch->id => $batch->batch_code.' — '.$batch->name.' ('.$batch->current_quantity.' available)',
                                ])
                                ->all())
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('product_type') === Sale::PRODUCT_CHICK)
                            ->required(fn (Get $get): bool => $get('product_type') === Sale::PRODUCT_CHICK),
                    ])
                    ->columns(2),

                Section::make('Sale amount')
                    ->schema([
                        TextInput::make('quantity')
                            ->label(fn (Get $get): string => match ($get('product_type')) {
                                Sale::PRODUCT_EGG => 'Egg Quantity',
                                Sale::PRODUCT_PARENT_HEN,
                                Sale::PRODUCT_PARENT_ROOSTER,
                                Sale::PRODUCT_POULTRY,
                                Sale::PRODUCT_CHICK => 'Bird Quantity',
                                default => 'Quantity',
                            })
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->live()
                            ->required(),

                        TextInput::make('unit_weight_kg')
                            ->label('Weight per Bird (kg, optional)')
                            ->numeric()
                            ->minValue(0.001)
                            ->step(0.001)
                            ->live()
                            ->visible(fn (Get $get): bool => in_array($get('product_type'), [
                                Sale::PRODUCT_PARENT_HEN,
                                Sale::PRODUCT_PARENT_ROOSTER,
                                Sale::PRODUCT_POULTRY,
                            ], true)),

                        TextInput::make('unit_price')
                            ->label(fn (Get $get): string => filled($get('unit_weight_kg'))
                                ? 'Price per kg (৳)'
                                : 'Unit Price (৳)')
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->live()
                            ->prefix('৳')
                            ->required(),

                        TextInput::make('discount_amount')
                            ->label('Discount (৳)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->live()
                            ->default(0)
                            ->prefix('৳')
                            ->required(),

                        Placeholder::make('estimated_total')
                            ->label('Invoice Total')
                            ->content(function (Get $get): string {
                                $quantity = max(0, (int) $get('quantity'));
                                $price = max(0, (float) $get('unit_price'));
                                $weight = max(0, (float) ($get('unit_weight_kg') ?: 1));
                                $discount = max(0, (float) $get('discount_amount'));
                                $total = max(0, ($quantity * $weight * $price) - $discount);

                                return '৳ '.number_format($total, 2);
                            }),
                    ])
                    ->columns(2),

                Section::make('Payment')
                    ->schema([
                        TextInput::make('paid_amount')
                            ->label('Paid Now (৳)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('৳')
                            ->required(),

                        Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'cash' => 'Cash',
                                'bkash' => 'bKash',
                                'nagad' => 'Nagad',
                                'bank' => 'Bank Transfer',
                            ])
                            ->default('cash')
                            ->required(fn (Get $get): bool => (float) $get('paid_amount') > 0),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}

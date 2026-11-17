<?php

namespace App\Filament\Resources\SupplyPurchases;

use App\Filament\Resources\SupplyPurchases\RelationManagers\PaymentsRelationManager;
use App\Models\Supplier;
use App\Models\SupplyProduct;
use App\Models\SupplyPurchase;
use App\Services\SupplyInventoryService;
use App\Support\SafeDeleteAction;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupplyPurchaseResource extends Resource
{
    protected static ?string $model = SupplyPurchase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Feed & Medicine Purchases';

    protected static string|\UnitEnum|null $navigationGroup = 'Feed & Expenses';

    protected static ?int $navigationSort = 32;

    protected static ?string $recordTitleAttribute = 'purchase_number';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Purchase and inventory receipt')->columns(2)->schema([
                Select::make('supplier_id')
                    ->label('Supplier')
                    ->options(fn (): array => Supplier::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('name')->required()->maxLength(150),
                        TextInput::make('phone_number')->tel()->maxLength(30),
                    ])
                    ->createOptionUsing(fn (array $data): int => (int) Supplier::query()->create($data)->getKey())
                    ->required(),
                Select::make('supply_product_id')
                    ->label('Feed or medicine')
                    ->options(fn (): array => SupplyProduct::query()->where('is_active', true)->orderBy('name')->get()->mapWithKeys(fn (SupplyProduct $product): array => [
                        $product->id => $product->name.' ('.ucfirst($product->type).')',
                    ])->all())
                    ->searchable()->required(),
                DatePicker::make('purchased_on')->default(today())->required()->maxDate(today()),
                TextInput::make('quantity')->numeric()->minValue(0.001)->step(0.001)->required(),
                TextInput::make('unit_cost')->label('Unit cost')->numeric()->minValue(0.01)->required()->prefix('BDT'),
                DatePicker::make('expires_on')->label('Lot expiry (required for medicine)'),
                TextInput::make('paid_amount')->numeric()->minValue(0)->default(0)->prefix('BDT')->live(),
                Select::make('payment_method')->options([
                    'cash' => 'Cash', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'Bank',
                ])->visible(fn (Get $get): bool => (float) $get('paid_amount') > 0),
                TextInput::make('supplier_invoice')->maxLength(100),
                Textarea::make('notes')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Inventory purchase')->columns(2)->schema([
                TextEntry::make('purchase_number')->label('Purchase')->copyable(),
                TextEntry::make('purchased_on')->date('d M Y'),
                TextEntry::make('supplier.name'),
                TextEntry::make('product.name')->label('Product'),
                TextEntry::make('quantity')->numeric(decimalPlaces: 3)->suffix(fn (SupplyPurchase $record): string => ' '.$record->product?->unit),
                TextEntry::make('lot.expires_on')->label('Lot expiry')->date('d M Y')->placeholder('Not recorded'),
                TextEntry::make('supplier_invoice')->label('Supplier invoice')->placeholder('—'),
                TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
            ]),
            Section::make('Inventory asset and payable')->columns(2)->schema([
                TextEntry::make('total_amount')->label('Inventory cost')->money('BDT'),
                TextEntry::make('paid_amount')->label('Paid')->money('BDT'),
                TextEntry::make('due_amount')->label('Supplier payable')->money('BDT'),
                TextEntry::make('payment_status')->badge(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('purchase_number')->label('Purchase')->searchable()->copyable()->sortable(),
            TextColumn::make('purchased_on')->date('d M Y')->sortable(),
            TextColumn::make('supplier.name')->searchable()->sortable(),
            TextColumn::make('product.name')->label('Product')->searchable(),
            TextColumn::make('lot.expires_on')->label('Expiry')->date('d M Y')->placeholder('Not applicable')->sortable(),
            TextColumn::make('quantity')->numeric(decimalPlaces: 3)->description(fn (SupplyPurchase $record): string => $record->product?->unit ?? ''),
            TextColumn::make('total_amount')->money('BDT')->sortable(),
            TextColumn::make('paid_amount')->money('BDT'),
            TextColumn::make('due_amount')->money('BDT')->color(fn (string $state): string => (float) $state > 0 ? 'danger' : 'success')->sortable(),
            TextColumn::make('payment_status')->badge()->color(fn (string $state): string => match ($state) {
                SupplyPurchase::PAYMENT_PAID => 'success',
                SupplyPurchase::PAYMENT_PARTIAL => 'warning',
                default => 'danger',
            }),
        ])->filters([
            SelectFilter::make('payment_status')->options([
                SupplyPurchase::PAYMENT_DUE => 'Due',
                SupplyPurchase::PAYMENT_PARTIAL => 'Partially paid',
                SupplyPurchase::PAYMENT_PAID => 'Paid',
            ]),
        ])->defaultSort('purchased_on', 'desc')
            ->recordActions([
                ViewAction::make(),
                SafeDeleteAction::make('feed or medicine purchase'),
                Action::make('recordPayment')
                    ->label('Pay supplier')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->visible(fn (SupplyPurchase $record): bool => (float) $record->due_amount > 0)
                    ->schema([
                        TextInput::make('amount')->numeric()->minValue(0.01)->required()->prefix('BDT'),
                        DatePicker::make('paid_on')->default(today())->maxDate(today())->required(),
                        Select::make('payment_method')->required()->options([
                            'cash' => 'Cash', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'Bank',
                        ]),
                        TextInput::make('reference')->maxLength(100),
                        Textarea::make('notes')->rows(2),
                    ])
                    ->action(fn (SupplyPurchase $record, array $data) => app(SupplyInventoryService::class)->recordPayment($record, $data)),
            ]);
    }

    public static function getRelations(): array
    {
        return [PaymentsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplyPurchases::route('/'),
            'create' => Pages\CreateSupplyPurchase::route('/create'),
            'view' => Pages\ViewSupplyPurchase::route('/{record}'),
        ];
    }
}

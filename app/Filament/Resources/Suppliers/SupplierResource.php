<?php

namespace App\Filament\Resources\Suppliers;

use App\Filament\Resources\Suppliers\Pages\ViewSupplier;
use App\Filament\Resources\Suppliers\RelationManagers\PurchasesRelationManager;
use App\Models\Supplier;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $navigationLabel = 'Suppliers';

    protected static string|\UnitEnum|null $navigationGroup = 'Farm Operations';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Supplier details')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(150),
                TextInput::make('phone_number')->tel()->maxLength(30),
                TextInput::make('email')->email()->maxLength(150),
                Toggle::make('is_active')->default(true),
                Textarea::make('address')->rows(2)->columnSpanFull(),
                Textarea::make('notes')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('phone_number')->label('Phone')->searchable()->placeholder('—'),
            TextColumn::make('purchases_count')->counts('purchases')->label('Purchases')->sortable(),
            TextColumn::make('purchases_sum_due_amount')->sum('purchases', 'due_amount')->label('Payable due')->money('BDT')->sortable(),
            IconColumn::make('is_active')->boolean()->label('Active'),
            TextColumn::make('updated_at')->since()->label('Updated')->sortable(),
        ])->recordActions([ViewAction::make(), EditAction::make()])->defaultSort('name');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Supplier profile')->columns(2)->schema([
                TextEntry::make('name')->label('Supplier'),
                TextEntry::make('phone_number')->label('Phone')->placeholder('Not provided'),
                TextEntry::make('email')->label('Email')->placeholder('Not provided'),
                TextEntry::make('is_active')->label('Status')->badge(),
                TextEntry::make('address')->columnSpanFull()->placeholder('Not provided'),
                TextEntry::make('notes')->columnSpanFull()->placeholder('No additional notes'),
                TextEntry::make('purchase_count')->label('Purchases')->state(fn (Supplier $record): int => $record->purchases()->count()),
                TextEntry::make('outstanding_due')->label('Outstanding Payable')->state(fn (Supplier $record): string => '৳ '.number_format((float) $record->purchases()->sum('due_amount'), 2)),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [PurchasesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'view' => ViewSupplier::route('/{record}'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}

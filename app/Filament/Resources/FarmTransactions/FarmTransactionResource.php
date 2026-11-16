<?php

namespace App\Filament\Resources\FarmTransactions;

use App\Filament\Resources\FarmTransactions\Pages\CreateFarmTransaction;
use App\Filament\Resources\FarmTransactions\Pages\ListFarmTransactions;
use App\Filament\Resources\FarmTransactions\Pages\ViewFarmTransaction;
use App\Filament\Resources\FarmTransactions\Schemas\FarmTransactionForm;
use App\Filament\Resources\FarmTransactions\Schemas\FarmTransactionInfolist;
use App\Filament\Resources\FarmTransactions\Tables\FarmTransactionsTable;
use App\Models\FarmTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FarmTransactionResource extends Resource
{
    protected static ?string $model = FarmTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Other Income & Expenses';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return FarmTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FarmTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FarmTransactionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFarmTransactions::route('/'),
            'create' => CreateFarmTransaction::route('/create'),
            'view' => ViewFarmTransaction::route('/{record}'),
        ];
    }
}

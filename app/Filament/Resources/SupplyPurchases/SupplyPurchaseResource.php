<?php

namespace App\Filament\Resources\SupplyPurchases;

use App\Models\FarmTransaction;
use App\Support\SafeDeleteAction;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplyPurchaseResource extends Resource
{
    protected static ?string $model = FarmTransaction::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Feed & Medicine Purchases';

    protected static string|\UnitEnum|null $navigationGroup = 'Feed & Expenses';

    protected static ?int $navigationSort = 32;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('transaction_type', FarmTransaction::TYPE_EXPENSE)
            ->whereIn('category', [
                FarmTransaction::EXPENSE_FEED,
                FarmTransaction::EXPENSE_MEDICINE,
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Feed & Medicine Purchase')
                ->description('Record your farm feed and medicine expenses.')
                ->columns(2)
                ->schema([

                    Hidden::make('transaction_type')
                        ->default(FarmTransaction::TYPE_EXPENSE)
                        ->required(),

                    Select::make('category')
                        ->label('Purchase Type')
                        ->options([
                            FarmTransaction::EXPENSE_FEED => 'Feed',
                            FarmTransaction::EXPENSE_MEDICINE => 'Medicine',
                        ])
                        ->native(false)
                        ->required(),

                    DatePicker::make('transaction_on')
                        ->label('Purchase Date')
                        ->default(today())
                        ->maxDate(today())
                        ->native(false)
                        ->required(),

                    TextInput::make('amount')
                        ->label('Total Amount')
                        ->numeric()
                        ->minValue(0.01)
                        ->maxValue(9999999999.99)
                        ->step(0.01)
                        ->prefix('BDT')
                        ->required(),

                    Textarea::make('notes')
                        ->label('Note')
                        ->placeholder('Enter an optional purchase note...')
                        ->rows(3)
                        ->columnSpanFull(),

                ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('transaction_on')
                    ->label('Purchase Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Purchase Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        FarmTransaction::EXPENSE_FEED => 'Feed',
                        FarmTransaction::EXPENSE_MEDICINE => 'Medicine',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        FarmTransaction::EXPENSE_FEED => 'info',
                        FarmTransaction::EXPENSE_MEDICINE => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('amount')
                    ->label('Total Amount')
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('notes')
                    ->label('Note')
                    ->limit(45)
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Recorded At')
                    ->dateTime('d M Y, h:i A')
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([

                SelectFilter::make('category')
                    ->label('Purchase Type')
                    ->options([
                        FarmTransaction::EXPENSE_FEED => 'Feed',
                        FarmTransaction::EXPENSE_MEDICINE => 'Medicine',
                    ]),

            ])
            ->recordActions([
                SafeDeleteAction::make('feed or medicine purchase'),
            ])
            ->defaultSort('transaction_on', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplyPurchases::route('/'),
            'create' => Pages\CreateSupplyPurchase::route('/create'),
        ];
    }
}

<?php

namespace App\Filament\Resources\SupplyUsages;

use App\Models\ChickBatch;
use App\Models\ParentFlock;
use App\Models\SupplyProduct;
use App\Models\SupplyUsage;
use BackedEnum;
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

class SupplyUsageResource extends Resource
{
    protected static ?string $model = SupplyUsage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static ?string $navigationLabel = 'Feed & Medicine Usage';

    protected static string|\UnitEnum|null $navigationGroup = 'Farm Operations';

    protected static ?int $navigationSort = 33;

    protected static ?string $recordTitleAttribute = 'usage_number';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Record inventory consumption')->columns(2)->schema([
                Select::make('supply_product_id')
                    ->label('Feed or medicine')
                    ->options(fn (): array => SupplyProduct::query()->where('is_active', true)->orderBy('name')->get()->mapWithKeys(fn (SupplyProduct $product): array => [
                        $product->id => $product->name.' ('.ucfirst($product->type).')',
                    ])->all())
                    ->searchable()->live()->required(),
                Select::make('usage_type')
                    ->label('Record type')
                    ->options(fn (Get $get): array => SupplyProduct::query()->find($get('supply_product_id'))?->type === SupplyProduct::TYPE_MEDICINE
                        ? ['treatment' => 'Treatment', 'vaccination' => 'Vaccination']
                        : ['feed_consumption' => 'Feed consumption'])
                    ->visible(fn (Get $get): bool => SupplyProduct::query()->find($get('supply_product_id'))?->type === SupplyProduct::TYPE_MEDICINE)
                    ->live(),
                TextInput::make('diagnosis')
                    ->label('Disease / diagnosis')
                    ->maxLength(150)
                    ->visible(fn (Get $get): bool => SupplyProduct::query()->find($get('supply_product_id'))?->type === SupplyProduct::TYPE_MEDICINE),
                TextInput::make('dosage')
                    ->label('Dosage')
                    ->maxLength(100)
                    ->required(fn (Get $get): bool => $get('usage_type') === 'vaccination')
                    ->visible(fn (Get $get): bool => SupplyProduct::query()->find($get('supply_product_id'))?->type === SupplyProduct::TYPE_MEDICINE),
                TextInput::make('administered_by')
                    ->label('Administered by')
                    ->maxLength(100)
                    ->visible(fn (Get $get): bool => SupplyProduct::query()->find($get('supply_product_id'))?->type === SupplyProduct::TYPE_MEDICINE),
                DatePicker::make('next_dose_on')
                    ->label('Next vaccination date (optional)')
                    ->minDate(fn (Get $get): ?string => $get('used_on'))
                    ->visible(fn (Get $get): bool => $get('usage_type') === 'vaccination'),
                DatePicker::make('used_on')->default(today())->required()->maxDate(today()),
                Select::make('source_type')->label('Usage for')->required()->options([
                    'parent_flock' => 'Parent flock',
                    'chick_batch' => 'Chick batch',
                ])->live(),
                TextInput::make('quantity')->numeric()->minValue(0.001)->step(0.001)->required(),
                Select::make('parent_flock_id')
                    ->label('Parent flock')
                    ->options(fn (): array => ParentFlock::query()->where('status', ParentFlock::STATUS_ACTIVE)->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()->visible(fn (Get $get): bool => $get('source_type') === 'parent_flock')
                    ->required(fn (Get $get): bool => $get('source_type') === 'parent_flock'),
                Select::make('chick_batch_id')
                    ->label('Chick batch')
                    ->options(fn (): array => ChickBatch::query()->where('status', ChickBatch::STATUS_ACTIVE)->orderBy('hatch_date')->get()->mapWithKeys(fn (ChickBatch $batch): array => [
                        $batch->id => $batch->batch_code.' — '.$batch->name,
                    ])->all())
                    ->searchable()->visible(fn (Get $get): bool => $get('source_type') === 'chick_batch')
                    ->required(fn (Get $get): bool => $get('source_type') === 'chick_batch'),
                Textarea::make('notes')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Consumption record')->columns(2)->schema([
                TextEntry::make('usage_number')->label('Usage'),
                TextEntry::make('used_on')->date('d M Y'),
                TextEntry::make('product.name')->label('Product'),
                TextEntry::make('usage_type')->label('Record type')->badge(),
                TextEntry::make('quantity')->numeric(decimalPlaces: 3),
                TextEntry::make('parentFlock.name')->label('Parent flock')->placeholder('—'),
                TextEntry::make('chickBatch.batch_code')->label('Chick batch')->placeholder('—'),
                TextEntry::make('total_cost')->money('BDT')->label('FIFO cost captured'),
                TextEntry::make('diagnosis')->label('Disease / diagnosis')->placeholder('—'),
                TextEntry::make('dosage')->label('Dosage')->placeholder('—'),
                TextEntry::make('administered_by')->label('Administered by')->placeholder('—'),
                TextEntry::make('next_dose_on')->label('Next dose')->date('d M Y')->placeholder('—'),
                TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('usage_number')->label('Usage')->searchable()->copyable()->sortable(),
            TextColumn::make('used_on')->date('d M Y')->sortable(),
            TextColumn::make('product.name')->label('Product')->searchable(),
            TextColumn::make('usage_type')->label('Record type')->badge(),
            TextColumn::make('quantity')->numeric(decimalPlaces: 3),
            TextColumn::make('parentFlock.name')->label('Flock')->placeholder('—'),
            TextColumn::make('chickBatch.batch_code')->label('Chick batch')->placeholder('—'),
            TextColumn::make('total_cost')->money('BDT')->label('FIFO cost')->sortable(),
        ])->filters([
            SelectFilter::make('supply_product_id')->label('Product')->relationship('product', 'name')->searchable(),
        ])->defaultSort('used_on', 'desc')
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplyUsages::route('/'),
            'create' => Pages\CreateSupplyUsage::route('/create'),
            'view' => Pages\ViewSupplyUsage::route('/{record}'),
        ];
    }
}

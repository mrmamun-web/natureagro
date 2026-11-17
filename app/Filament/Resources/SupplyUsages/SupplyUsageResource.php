<?php

namespace App\Filament\Resources\SupplyUsages;

use App\Models\ChickBatch;
use App\Models\ParentFlock;
use App\Models\SupplyProduct;
use App\Models\SupplyUsage;
use App\Support\SafeDeleteAction;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = SupplyUsage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static ?string $navigationLabel = 'Feed & Medicine Use';

    protected static string|\UnitEnum|null $navigationGroup = 'Feed & Expenses';

    protected static ?int $navigationSort = 33;

    protected static ?string $recordTitleAttribute = 'usage_number';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Feed or medicine used')
                ->description('Record the quantity used for a parent flock or chick batch.')
                ->columns(2)
                ->schema([
                    Select::make('supply_product_id')
                        ->label('Feed or medicine')
                        ->options(fn (): array => SupplyProduct::query()->where('is_active', true)->orderBy('name')->get()->mapWithKeys(fn (SupplyProduct $product): array => [
                            $product->id => $product->name.' ('.ucfirst($product->type).')',
                        ])->all())
                        ->searchable()->live()->required(),
                    DatePicker::make('used_on')->label('Date')->default(today())->required()->maxDate(today()),
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
            ->recordActions([
                ViewAction::make(),
                SafeDeleteAction::make('feed or medicine usage'),
            ]);
    }

    public static function getPages(): array
    {
        return [];
    }
}

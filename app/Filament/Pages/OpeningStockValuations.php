<?php

namespace App\Filament\Pages;

use App\Models\ChickBatch;
use App\Models\EggStock;
use App\Models\IncubationBatch;
use App\Models\Poultry;
use App\Services\InventoryCostService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;

class OpeningStockValuations extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Opening Stock Valuation';

    protected static ?int $navigationSort = 20;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance & Reports';

    protected static ?string $title = 'Value Existing Inventory';

    protected string $view = 'filament.pages.opening-stock-valuations';

    public array $openingCosts = [];

    protected function getViewData(): array
    {
        $costs = app(InventoryCostService::class);

        return [
            'chickBatches' => ChickBatch::query()->where('current_quantity', '>', 0)->orderBy('hatch_date')->get()
                ->filter(fn (ChickBatch $batch): bool => $costs->needsOpeningValuation('chick_batch', $batch->id))
                ->map(fn (ChickBatch $batch): array => ['id' => $batch->id, 'name' => $batch->batch_code.' · '.$batch->name, 'quantity' => $batch->current_quantity]),
            'poultries' => Poultry::query()->where('current_quantity', '>', 0)->orderBy('name')->get()
                ->filter(fn (Poultry $poultry): bool => $costs->needsOpeningValuation('poultry', $poultry->id))
                ->map(fn (Poultry $poultry): array => ['id' => $poultry->id, 'name' => $poultry->poultry_code.' · '.$poultry->name, 'quantity' => $poultry->current_quantity]),
            'eggStocks' => EggStock::query()->with('parentFlock')->where('good_available', '>', 0)->get()
                ->filter(fn (EggStock $stock): bool => $costs->needsOpeningValuation('egg_stock', $stock->id))
                ->map(fn (EggStock $stock): array => ['id' => $stock->id, 'name' => $stock->parentFlock->name, 'quantity' => $stock->good_available]),
            'incubationBatches' => IncubationBatch::query()->where('status', IncubationBatch::STATUS_INCUBATING)->orderBy('incubated_on')->get()
                ->filter(fn (IncubationBatch $batch): bool => $costs->needsOpeningValuation('incubation_batch', $batch->id))
                ->map(fn (IncubationBatch $batch): array => ['id' => $batch->id, 'name' => $batch->batch_code, 'quantity' => $batch->eggs_set]),
        ];
    }

    public function valueChickBatch(int $id): void
    {
        $this->value('chick_batch', $id, 'chick_batch', $id);
    }

    public function valuePoultry(int $id): void
    {
        $this->value('poultry', $id, 'poultry', $id);
    }

    public function valueEggStock(int $id): void
    {
        $this->value('egg_stock', $id, 'egg_stock', $id);
    }

    public function valueIncubationBatch(int $id): void
    {
        $this->value('incubation_batch', $id, 'incubation_batch', $id);
    }

    private function value(
        string $inputGroup,
        int $inputId,
        string $stockType,
        int $stockId,
    ): void {
        $path = $inputGroup.'.'.$inputId;
        $property = 'openingCosts.'.$path;
        $validated = Validator::make($this->all(), [
            $property => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
        ])->validate();

        app(InventoryCostService::class)->valueOpeningStock(
            $stockType,
            $stockId,
            null,
            (float) data_get($validated, $property),
            today()->toDateString(),
            null,
        );

        data_set($this->openingCosts, $path, null);
        Notification::make()->title('Opening stock valuation saved')->success()->send();
    }
}

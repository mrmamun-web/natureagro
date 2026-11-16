<?php

namespace App\Filament\Pages;

use App\Models\ChickBatch;
use App\Models\EggStock;
use App\Models\IncubationBatch;
use App\Models\ParentFlock;
use App\Models\Poultry;
use App\Services\InventoryCostService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class OpeningStockValuations extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Opening Stock Valuation';

    protected static ?int $navigationSort = 20;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $title = 'Value Existing Inventory';

    protected string $view = 'filament.pages.opening-stock-valuations';

    public array $openingCosts = [];

    public array $openingEggUnitsPerHen = [];

    protected function getViewData(): array
    {
        $costs = app(InventoryCostService::class);
        $defaultEggUnitsPerHen = (int) DB::table('inventory_costing_settings')->where('id', 1)->value('lifetime_good_eggs_per_hen');

        return [
            'defaultEggUnitsPerHen' => $defaultEggUnitsPerHen,
            'parentFlocks' => ParentFlock::query()->get()
                ->flatMap(function (ParentFlock $flock) use ($costs, $defaultEggUnitsPerHen): array {
                    $items = [];
                    foreach (['male', 'female'] as $gender) {
                        $quantity = $gender === 'male' ? $flock->current_male_quantity : $flock->current_female_quantity;
                        if ($quantity > 0 && $costs->needsOpeningValuation('parent_flock', $flock->id, $gender)) {
                            $items[] = [
                                'id' => $flock->id,
                                'name' => $flock->name,
                                'gender' => $gender,
                                'quantity' => $quantity,
                                'default_egg_units_per_hen' => $defaultEggUnitsPerHen,
                            ];
                        }
                    }

                    return $items;
                }),
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

    public function valueParentFlock(int $id, string $gender): void
    {
        if (! in_array($gender, ['male', 'female'], true)) {
            throw ValidationException::withMessages(['openingCosts.parent_flock' => 'Select a valid flock inventory.']);
        }

        $productionUnitsPerUnit = null;
        if ($gender === 'female') {
            $default = DB::table('inventory_costing_settings')->where('id', 1)->value('lifetime_good_eggs_per_hen');
            $expectedEggsPerHen = data_get($this->openingEggUnitsPerHen, $id);
            $validated = Validator::make([
                'production_units_per_unit' => filled($expectedEggsPerHen) ? $expectedEggsPerHen : $default,
            ], [
                'production_units_per_unit' => ['required', 'integer', 'min:1', 'max:1000000'],
            ])->validate();
            $productionUnitsPerUnit = (float) $validated['production_units_per_unit'];
        }

        $this->value('parent_flock', $id, 'parent_flock', $id, $gender, $productionUnitsPerUnit);
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
        ?string $gender = null,
        ?float $productionUnitsPerUnit = null,
    ): void
    {
        $path = $gender === null
            ? $inputGroup.'.'.$inputId
            : $inputGroup.'.'.$inputId.'.'.$gender;
        $property = 'openingCosts.'.$path;
        $validated = Validator::make($this->all(), [
            $property => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
        ])->validate();

        app(InventoryCostService::class)->valueOpeningStock(
            $stockType,
            $stockId,
            $gender,
            (float) data_get($validated, $property),
            today()->toDateString(),
            $productionUnitsPerUnit,
        );

        data_set($this->openingCosts, $path, null);
        Notification::make()->title('Opening stock valuation saved')->success()->send();
    }
}

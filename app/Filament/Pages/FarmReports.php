<?php

namespace App\Filament\Pages;

use App\Models\ChickBatchMovement;
use App\Models\FarmTransaction;
use App\Models\FlockDailyRecord;
use App\Models\IncubationBatch;
use App\Models\InventoryCostLayer;
use App\Models\InventoryCostMovement;
use App\Models\Sale;
use App\Models\SupplyProduct;
use App\Models\SupplyPurchase;
use App\Models\SupplyStockMovement;
use App\Models\SupplyUsage;
use App\Support\XlsxReportExporter;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FarmReports extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Farm Reports';

    protected static ?int $navigationSort = 10;

    protected static string|\UnitEnum|null $navigationGroup = 'Reports & Analytics';

    protected static ?string $title = 'Farm Reports & Analytics';

    protected string $view = 'filament.pages.farm-reports';

    public string $requestedStartMonth = '';

    public string $requestedEndMonth = '';

    public string $startDate = '';

    public string $endDate = '';

    public function mount(): void
    {
        $this->requestedStartMonth = now()->format('Y-m');
        $this->requestedEndMonth = now()->format('Y-m');
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = today()->toDateString();
    }

    public function applyFilters(): void
    {
        $data = Validator::make([
            'requestedStartMonth' => $this->requestedStartMonth,
            'requestedEndMonth' => $this->requestedEndMonth,
        ], [
            'requestedStartMonth' => ['required', 'date_format:Y-m'],
            'requestedEndMonth' => ['required', 'date_format:Y-m'],
        ])->validate();

        $monthStart = Carbon::parse($data['requestedStartMonth'].'-01')->startOfMonth();
        $monthEnd = Carbon::parse($data['requestedEndMonth'].'-01')->startOfMonth();

        if ($monthStart->isAfter($monthEnd)) {
            throw ValidationException::withMessages([
                'requestedStartMonth' => 'The start month must be on or before the end month.',
            ]);
        }

        $this->startDate = $monthStart->toDateString();
        $this->endDate = $monthEnd->isSameMonth(today())
            ? today()->toDateString()
            : $monthEnd->endOfMonth()->toDateString();
    }

    protected function getViewData(): array
    {
        $dates = $this->validatedDates();

        $daily = FlockDailyRecord::query()->whereBetween('recorded_on', [$dates['startDate'], $dates['endDate']]);
        $chickDeaths = ChickBatchMovement::query()
            ->where('movement_type', ChickBatchMovement::TYPE_MORTALITY)
            ->whereBetween('moved_on', [$dates['startDate'], $dates['endDate']])
            ->sum('quantity');
        $incubated = IncubationBatch::query()->whereBetween('incubated_on', [$dates['startDate'], $dates['endDate']]);
        $hatched = IncubationBatch::query()
            ->whereNotNull('actual_hatch_on')
            ->whereBetween('actual_hatch_on', [$dates['startDate'], $dates['endDate']]);
        $sales = Sale::query()->whereBetween('sold_on', [$dates['startDate'], $dates['endDate']]);
        $transactions = FarmTransaction::query()->whereBetween('transaction_on', [$dates['startDate'], $dates['endDate']]);
        $supplyUsages = SupplyUsage::query()->whereBetween('used_on', [$dates['startDate'], $dates['endDate']]);
        $inventoryCostMovements = InventoryCostMovement::query();
        $costOfGoodsSold = (float) (clone $sales)->sum('cost_of_goods_sold');
        $mortalityLosses = (float) (clone $inventoryCostMovements)
            ->where('movement_type', 'mortality')
            ->whereBetween('moved_on', [$dates['startDate'], $dates['endDate']])
            ->where('cost_delta', '<', 0)
            ->sum(DB::raw('ABS(cost_delta)'));
        $hatchingLosses = (float) FarmTransaction::query()
            ->where('category', 'production_loss')
            ->whereBetween('transaction_on', [$dates['startDate'], $dates['endDate']])
            ->sum(DB::raw("CASE WHEN transaction_type = 'expense' THEN amount ELSE -amount END"));
        $inventoryTrackingStart = DB::table('inventory_costing_settings')->where('id', 1)->value('costing_started_on');
        $uncostedSales = (clone $sales)->whereNull('cost_of_goods_sold')->count();
        $unvaluedStock = InventoryCostLayer::query()
            ->where('remaining_quantity', '>', 0)
            ->whereNull('remaining_cost')
            ->count();
        $remainingBreederCost = (float) InventoryCostLayer::query()
            ->where('inventory_type', 'parent_flock')
            ->whereIn('gender', ['male', 'female'])
            ->where('remaining_quantity', '>', 0)
            ->whereNotNull('remaining_cost')
            ->sum('remaining_cost');
        $breederCostMethod = DB::table('inventory_costing_settings')->where('id', 1)->value('breeder_cost_method');
        $lifetimeGoodEggsPerHen = DB::table('inventory_costing_settings')->where('id', 1)->value('lifetime_good_eggs_per_hen');
        $breederCostAllocationPending = $breederCostMethod !== 'units_of_production'
            || ! is_numeric($lifetimeGoodEggsPerHen)
            || (int) $lifetimeGoodEggsPerHen < 1
            || InventoryCostLayer::query()
                ->where('inventory_type', 'parent_flock')
                ->where('gender', 'female')
                ->where('total_cost', '>', 0)
                ->where('remaining_quantity', '>', 0)
                ->where('remaining_cost', '>', 0)
                ->where(function ($query): void {
                    $query->whereNull('remaining_production_units')
                        ->orWhere('remaining_production_units', '<=', 0);
                })
                ->exists();
        $beforeCostTracking = $inventoryTrackingStart !== null && $dates['startDate'] < $inventoryTrackingStart;
        $profitLossComplete = $uncostedSales === 0
            && $unvaluedStock === 0
            && ! $breederCostAllocationPending
            && ! $beforeCostTracking;
        $grossProfit = (float) (clone $sales)->sum('total_amount') - $costOfGoodsSold;
        $otherIncome = (float) (clone $transactions)
            ->where('transaction_type', FarmTransaction::TYPE_INCOME)
            ->where(function ($query): void {
                $query->whereNull('category')->orWhere('category', '!=', 'production_loss');
            })
            ->sum('amount');
        $otherExpenses = (float) (clone $transactions)
            ->where('transaction_type', FarmTransaction::TYPE_EXPENSE)
            ->where(function ($query): void {
                $query->whereNull('category')->orWhere('category', '!=', 'production_loss');
            })
            ->sum('amount');
        $supplyStockValue = fn (string $dateOperator, string $date): float => (float) SupplyStockMovement::query()
            ->whereDate('moved_on', $dateOperator, $date)
            ->sum(DB::raw("CASE WHEN movement_type = 'purchase' THEN total_cost WHEN movement_type = 'usage' THEN -total_cost ELSE 0 END"));

        return [
            'dates' => $dates,
            'eggsCollected' => (int) (clone $daily)->sum('eggs_collected'),
            'goodEggs' => (int) (clone $daily)->sum('good_eggs'),
            'damagedEggs' => (int) (clone $daily)->sum('damaged_eggs'),
            'flockDeaths' => (int) (clone $daily)->sum(DB::raw('male_mortality + female_mortality')),
            'chickDeaths' => (int) $chickDeaths,
            'eggsSet' => (int) (clone $incubated)->sum('eggs_set'),
            'chicksHatched' => (int) (clone $hatched)->sum('hatched_chicks'),
            'failedEggs' => (int) (clone $hatched)->sum('failed_eggs'),
            'eggSales' => (int) (clone $sales)->where('product_type', Sale::PRODUCT_EGG)->sum('quantity'),
            'chickSales' => (int) (clone $sales)->where('product_type', Sale::PRODUCT_CHICK)->sum('quantity'),
            'birdSales' => (int) (clone $sales)->whereIn('product_type', [
                Sale::PRODUCT_PARENT_HEN,
                Sale::PRODUCT_PARENT_ROOSTER,
                Sale::PRODUCT_POULTRY,
            ])->sum('quantity'),
            'salesRevenue' => (float) (clone $sales)->sum('total_amount'),
            'salesDue' => (float) (clone $sales)->sum('due_amount'),
            'otherIncome' => $otherIncome,
            'otherExpenses' => $otherExpenses,
            'feedConsumed' => (float) (clone $supplyUsages)
                ->whereHas('product', fn ($query) => $query->where('type', SupplyProduct::TYPE_FEED))
                ->sum('quantity'),
            'feedCost' => (float) (clone $supplyUsages)
                ->whereHas('product', fn ($query) => $query->where('type', SupplyProduct::TYPE_FEED))
                ->sum('total_cost'),
            'medicineCost' => (float) (clone $supplyUsages)
                ->whereHas('product', fn ($query) => $query->where('type', SupplyProduct::TYPE_MEDICINE))
                ->sum('total_cost'),
            'directProductionCosts' => (float) (clone $supplyUsages)->sum('total_cost'),
            'vaccinations' => (int) (clone $supplyUsages)->where('usage_type', 'vaccination')->count(),
            'supplierDues' => (float) SupplyPurchase::query()->sum('due_amount'),
            'costOfGoodsSold' => $costOfGoodsSold,
            'grossProfit' => $grossProfit,
            'mortalityLosses' => $mortalityLosses,
            'hatchingLosses' => $hatchingLosses,
            'openingInventoryValue' => (float) (clone $inventoryCostMovements)
                ->whereDate('moved_on', '<', $dates['startDate'])
                ->sum('cost_delta') + $supplyStockValue('<', $dates['startDate']),
            'closingInventoryValue' => (float) (clone $inventoryCostMovements)
                ->whereDate('moved_on', '<=', $dates['endDate'])
                ->sum('cost_delta') + $supplyStockValue('<=', $dates['endDate']),
            'uncostedSales' => $uncostedSales,
            'unvaluedStock' => $unvaluedStock,
            'remainingBreederCost' => $remainingBreederCost,
            'breederCostAllocationPending' => $breederCostAllocationPending,
            'breederCostMethod' => $breederCostMethod,
            'lifetimeGoodEggsPerHen' => (int) ($lifetimeGoodEggsPerHen ?? 0),
            'costTrackingStart' => $inventoryTrackingStart,
            'profitLossComplete' => $profitLossComplete,
            'netProfit' => $profitLossComplete
                ? round($grossProfit + $otherIncome - $otherExpenses - $mortalityLosses - $hatchingLosses, 2)
                : null,
        ];
    }

    public function downloadCsv(): StreamedResponse
    {
        $this->applyFilters();
        $report = $this->getViewData();
        $rows = $this->reportRows($report);

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                throw new \RuntimeException('Unable to open the report output stream.');
            }

            foreach ($rows as $row) {
                fputcsv($stream, $row);
            }

            fclose($stream);
        }, 'nature-agro-report-'.$report['dates']['startDate'].'-to-'.$report['dates']['endDate'].'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadXlsx(XlsxReportExporter $exporter): BinaryFileResponse
    {
        $this->applyFilters();
        $report = $this->getViewData();
        $path = $exporter->create($this->reportRows($report));

        return response()->download(
            $path,
            'nature-agro-report-'.$report['dates']['startDate'].'-to-'.$report['dates']['endDate'].'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }

    private function reportRows(array $report): array
    {
        return [
            ['Nature Agro farm report', $report['dates']['startDate'].' to '.$report['dates']['endDate']],
            ['Category', 'Measure', 'Value'],
            ['Eggs', 'Collected', $report['eggsCollected']],
            ['Eggs', 'Good collected', $report['goodEggs']],
            ['Eggs', 'Damaged collected', $report['damagedEggs']],
            ['Incubation', 'Eggs set', $report['eggsSet']],
            ['Incubation', 'Chicks hatched', $report['chicksHatched']],
            ['Incubation', 'Failed eggs', $report['failedEggs']],
            ['Mortality', 'Parent flock birds', $report['flockDeaths']],
            ['Mortality', 'Chick batch birds', $report['chickDeaths']],
            ['Sales', 'Eggs sold', $report['eggSales']],
            ['Sales', 'Chicks sold', $report['chickSales']],
            ['Sales', 'Other birds sold', $report['birdSales']],
            ['Sales', 'Invoice revenue (BDT)', $report['salesRevenue']],
            ['Sales', 'Outstanding due on invoices (BDT)', $report['salesDue']],
            ['Feed', 'Consumed quantity', $report['feedConsumed']],
            ['Feed', 'FIFO consumption cost (BDT)', $report['feedCost']],
            ['Medicine', 'FIFO consumption cost (BDT)', $report['medicineCost']],
            ['Healthcare', 'Vaccinations recorded', $report['vaccinations']],
            ['Suppliers', 'Current outstanding payable (BDT)', $report['supplierDues']],
            ['Other transactions', 'Other income (BDT)', $report['otherIncome']],
            ['Other transactions', 'Other expenses (BDT)', $report['otherExpenses']],
            ['Profit and loss', 'COGS (BDT)', $report['costOfGoodsSold']],
            ['Profit and loss', 'Gross profit (BDT)', $report['profitLossComplete'] ? $report['grossProfit'] : 'INCOMPLETE'],
            ['Profit and loss', 'Mortality losses (BDT)', $report['mortalityLosses']],
            ['Profit and loss', 'Hatching losses (BDT)', $report['hatchingLosses']],
            ['Inventory', 'Remaining breeder flock value (BDT)', $report['remainingBreederCost']],
            ['Profit and loss', 'Breeder cost method', $report['breederCostMethod'] ?? 'NOT CONFIGURED'],
            ['Profit and loss', 'Default good eggs per hen', $report['lifetimeGoodEggsPerHen']],
            ['Inventory', 'Opening inventory value (BDT)', $report['openingInventoryValue']],
            ['Inventory', 'Closing inventory value (BDT)', $report['closingInventoryValue']],
            ['Profit and loss', 'Net profit (BDT)', $report['netProfit'] ?? 'INCOMPLETE'],
            ['Profit and loss', 'Status', $report['profitLossComplete'] ? 'Complete for tracked and valued records and configured breeder allocation' : 'Incomplete; see valuation and breeder-cost policy warnings'],
        ];
    }

    private function validatedDates(): array
    {
        return [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ];
    }
}

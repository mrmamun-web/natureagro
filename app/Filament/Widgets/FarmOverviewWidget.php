<?php

namespace App\Filament\Widgets;

use App\Models\ChickBatch;
use App\Models\EggStock;
use App\Models\FarmSetting;
use App\Models\FarmTransaction;
use App\Models\FlockDailyRecord;
use App\Models\IncubationBatch;
use App\Models\ParentFlock;
use App\Models\Poultry;
use App\Models\Sale;
use App\Models\SupplyLot;
use App\Models\SupplyProduct;
use App\Models\SupplyPurchase;
use App\Models\SupplyUsage;
use Filament\Widgets\Widget;

class FarmOverviewWidget extends Widget
{
    protected string $view = 'filament.widgets.farm-overview-widget';

    protected int|string|array $columnSpan = 'full';

    public ?FarmSetting $farmSetting = null;

    public function mount(): void
    {
        $this->farmSetting = FarmSetting::query()->first();
    }

    protected function getViewData(): array
    {
        $parentFlockStock = ParentFlock::query()
            ->active()
            ->selectRaw('
                COALESCE(SUM(current_male_quantity), 0) as male_stock,
                COALESCE(SUM(current_female_quantity), 0) as female_stock
            ')
            ->first();

        $roosterStock = (int) $parentFlockStock->male_stock;
        $henStock = (int) $parentFlockStock->female_stock;
        $totalParentBirds = $roosterStock + $henStock;
        $today = today();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $salesThisMonth = Sale::query()->whereBetween('sold_on', [$monthStart, $monthEnd]);
        $supplyUsageThisMonth = SupplyUsage::query()->whereBetween('used_on', [$monthStart, $monthEnd]);
        $vaccinationWindowEnd = today()->addDays(7)->toDateString();
        $activeBatches = ChickBatch::query()->where('status', ChickBatch::STATUS_ACTIVE);
        $eggCountsByDay = FlockDailyRecord::query()
            ->whereBetween('recorded_on', [now()->subDays(13)->toDateString(), $today->toDateString()])
            ->selectRaw('recorded_on, SUM(eggs_collected) as egg_count')
            ->groupBy('recorded_on')
            ->pluck('egg_count', 'recorded_on');
        $dailyEggTrend = collect(range(13, 0))
            ->map(function (int $daysAgo) use ($eggCountsByDay): array {
                $date = now()->subDays($daysAgo);
                $dateKey = $date->toDateString();

                return [
                    'date' => $date->format('d M Y'),
                    'label' => $date->format('d'),
                    'count' => (int) $eggCountsByDay->get($dateKey, 0),
                ];
            })
            ->all();

        return [
            'totalParentBirds' => $totalParentBirds,
            'henStock' => $henStock,
            'roosterStock' => $roosterStock,
            'activeParentFlocks' => (int) ParentFlock::query()->active()->count(),
            'eggsToday' => (int) FlockDailyRecord::query()->whereDate('recorded_on', $today)->sum('eggs_collected'),
            'eggsThisMonth' => (int) FlockDailyRecord::query()
                ->whereBetween('recorded_on', [$monthStart, $monthEnd])
                ->sum('eggs_collected'),
            'availableEggs' => (int) EggStock::query()->sum('good_available'),
            'reservedEggs' => (int) EggStock::query()->sum('reserved_for_hatching'),
            'liveChicks' => (int) $activeBatches->sum('current_quantity'),
            'activeChickBatches' => (int) ChickBatch::query()->where('status', ChickBatch::STATUS_ACTIVE)->count(),
            'hatchedThisMonth' => (int) IncubationBatch::query()
                ->where('status', IncubationBatch::STATUS_HATCHED)
                ->whereBetween('actual_hatch_on', [$monthStart, $monthEnd])
                ->sum('hatched_chicks'),
            'eggSalesToday' => (int) Sale::query()
                ->where('product_type', Sale::PRODUCT_EGG)
                ->whereDate('sold_on', $today)
                ->sum('quantity'),
            'poultrySalesThisMonth' => (int) (clone $salesThisMonth)
                ->whereIn('product_type', [
                    Sale::PRODUCT_PARENT_HEN,
                    Sale::PRODUCT_PARENT_ROOSTER,
                    Sale::PRODUCT_POULTRY,
                ])
                ->sum('quantity'),
            'chickSalesThisMonth' => (int) (clone $salesThisMonth)
                ->where('product_type', Sale::PRODUCT_CHICK)
                ->sum('quantity'),
            'salesRevenueThisMonth' => (float) (clone $salesThisMonth)->sum('total_amount'),
            'outstandingDues' => (float) Sale::query()->sum('due_amount'),
            'otherIncomeThisMonth' => (float) FarmTransaction::query()
                ->where('transaction_type', FarmTransaction::TYPE_INCOME)
                ->whereBetween('transaction_on', [$monthStart, $monthEnd])
                ->sum('amount'),
            'otherExpensesThisMonth' => (float) FarmTransaction::query()
                ->where('transaction_type', FarmTransaction::TYPE_EXPENSE)
                ->whereBetween('transaction_on', [$monthStart, $monthEnd])
                ->sum('amount'),
            'feedCostsThisMonth' => (float) (clone $supplyUsageThisMonth)
                ->whereHas('product', fn ($query) => $query->where('type', SupplyProduct::TYPE_FEED))
                ->sum('total_cost'),
            'medicineCostsThisMonth' => (float) (clone $supplyUsageThisMonth)
                ->whereHas('product', fn ($query) => $query->where('type', SupplyProduct::TYPE_MEDICINE))
                ->sum('total_cost'),
            'supplierDues' => (float) SupplyPurchase::query()->sum('due_amount'),
            'lowStockProducts' => SupplyProduct::query()
                ->where('is_active', true)
                ->where('minimum_stock', '>', 0)
                ->whereRaw(
                    '(SELECT COALESCE(SUM(l.remaining_quantity), 0) FROM supply_lots l WHERE l.supply_product_id = supply_products.id AND (supply_products.type <> ? OR l.expires_on >= ?)) <= supply_products.minimum_stock',
                    [SupplyProduct::TYPE_MEDICINE, $today->toDateString()],
                )
                ->orderBy('name')
                ->limit(6)
                ->get(),
            'upcomingVaccinations' => SupplyUsage::query()
                ->with(['product', 'parentFlock', 'chickBatch'])
                ->where('usage_type', 'vaccination')
                ->whereBetween('next_dose_on', [$today->toDateString(), $vaccinationWindowEnd])
                ->orderBy('next_dose_on')
                ->limit(5)
                ->get(),
            'expiringMedicineLots' => SupplyLot::query()
                ->with('product')
                ->whereNotNull('expires_on')
                ->whereBetween('expires_on', [$today->toDateString(), $today->copy()->addDays(30)->toDateString()])
                ->where('remaining_quantity', '>', 0)
                ->whereHas('product', fn ($query) => $query->where('type', SupplyProduct::TYPE_MEDICINE))
                ->orderBy('expires_on')
                ->limit(5)
                ->get(),
            'henPercentage' => $totalParentBirds > 0 ? round(($henStock / $totalParentBirds) * 100) : 0,
            'roosterPercentage' => $totalParentBirds > 0 ? round(($roosterStock / $totalParentBirds) * 100) : 0,
            'dailyEggTrend' => $dailyEggTrend,
            'latestPoultries' => Poultry::query()
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }
}

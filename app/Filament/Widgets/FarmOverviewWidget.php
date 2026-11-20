<?php

namespace App\Filament\Widgets;

use App\Models\ChickBatch;
use App\Models\FarmSetting;
use App\Models\FarmTransaction;
use App\Models\FlockDailyRecord;
use App\Models\IncubationBatch;
use App\Models\ParentFlock;
use App\Models\Sale;
use App\Models\SupplyProduct;
use App\Models\SupplyPurchase;
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
        $today = today();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $applyMonthRange = fn ($query, string $column) => $query
            ->whereDate($column, '>=', $monthStart)
            ->whereDate($column, '<=', $monthEnd);
        $supplyPurchasesThisMonth = fn () => $applyMonthRange(SupplyPurchase::query(), 'purchased_on');

        return [
            'totalParentBirds' => $roosterStock + $henStock,
            'henStock' => $henStock,
            'roosterStock' => $roosterStock,
            'eggsToday' => (int) FlockDailyRecord::query()->whereDate('recorded_on', $today)->sum('eggs_collected'),
            'currentEggStock' => (int) \App\Models\EggStock::query()->sum('good_available'),
            'eggsThisMonth' => (int) FlockDailyRecord::query()
                ->whereDate('recorded_on', '>=', $monthStart)
                ->whereDate('recorded_on', '<=', $monthEnd)
                ->sum('eggs_collected'),
            'eggsCurrentlyIncubating' => (int) IncubationBatch::query()
                ->where('status', IncubationBatch::STATUS_INCUBATING)
                ->sum('eggs_set'),
            'activeIncubationBatches' => (int) IncubationBatch::query()
                ->where('status', IncubationBatch::STATUS_INCUBATING)
                ->count(),
            'liveChicks' => (int) ChickBatch::query()
                ->where('source', ChickBatch::SOURCE_HATCHED)
                ->where('status', ChickBatch::STATUS_ACTIVE)
                ->sum('current_quantity'),
            'activeChickBatches' => (int) ChickBatch::query()
                ->where('source', ChickBatch::SOURCE_HATCHED)
                ->where('status', ChickBatch::STATUS_ACTIVE)
                ->count(),
            'hatchedThisMonth' => (int) IncubationBatch::query()
                ->where('status', IncubationBatch::STATUS_HATCHED)
                ->whereDate('actual_hatch_on', '>=', $monthStart)
                ->whereDate('actual_hatch_on', '<=', $monthEnd)
                ->sum('hatched_chicks'),
            'salesRevenueThisMonth' => (float) Sale::query()
                ->whereDate('sold_on', '>=', $monthStart)
                ->whereDate('sold_on', '<=', $monthEnd)
                ->sum('total_amount'),
            'eggsSoldThisMonth' => (int) Sale::query()
                ->where('product_type', Sale::PRODUCT_EGG)
                ->whereDate('sold_on', '>=', $monthStart)
                ->whereDate('sold_on', '<=', $monthEnd)
                ->sum('quantity'),
            'birdsSoldThisMonth' => (int) Sale::query()
                ->where('product_type', Sale::PRODUCT_CHICK)
                ->whereDate('sold_on', '>=', $monthStart)
                ->whereDate('sold_on', '<=', $monthEnd)
                ->sum('quantity'),
            'otherExpensesThisMonth' => (float) FarmTransaction::query()
                ->where('transaction_type', FarmTransaction::TYPE_EXPENSE)
                ->whereDate('transaction_on', '>=', $monthStart)
                ->whereDate('transaction_on', '<=', $monthEnd)
                ->sum('amount'),
            'feedCostsThisMonth' => (float) $supplyPurchasesThisMonth()
                ->whereHas('product', fn ($query) => $query->where('type', SupplyProduct::TYPE_FEED))
                ->sum('total_amount'),
            'medicineCostsThisMonth' => (float) $supplyPurchasesThisMonth()
                ->whereHas('product', fn ($query) => $query->where('type', SupplyProduct::TYPE_MEDICINE))
                ->sum('total_amount'),
        ];
    }
}

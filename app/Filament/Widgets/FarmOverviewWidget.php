<?php

namespace App\Filament\Widgets;

use App\Models\ChickBatch;
use App\Models\EggStock;
use App\Models\FarmSetting;
use App\Models\FarmTransaction;
use App\Models\FlockDailyRecord;
use App\Models\IncubationBatch;
use App\Models\ParentFlock;
use App\Models\Sale;
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
        // Farm settings
        $this->farmSetting = FarmSetting::query()->first();

        // Parent flock stock
        $parentFlockStock = ParentFlock::query()
            ->active()
            ->selectRaw('
                COALESCE(SUM(current_male_quantity), 0) as male_stock,
                COALESCE(SUM(current_female_quantity), 0) as female_stock
            ')
            ->first();

        $roosterStock = (int) ($parentFlockStock->male_stock ?? 0);
        $henStock = (int) ($parentFlockStock->female_stock ?? 0);

        // Date ranges
        $today = today();

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        // Current chick stock
        $liveChicks = (int) ChickBatch::query()
            ->where('source', ChickBatch::SOURCE_HATCHED)
            ->where('status', ChickBatch::STATUS_ACTIVE)
            ->sum('current_quantity');

        $activeChickBatches = (int) ChickBatch::query()
            ->where('source', ChickBatch::SOURCE_HATCHED)
            ->where('status', ChickBatch::STATUS_ACTIVE)
            ->count();

        // Monthly sales
        $salesThisMonth = Sale::query()
            ->whereDate('sold_on', '>=', $monthStart)
            ->whereDate('sold_on', '<=', $monthEnd);

        $salesRevenueThisMonth = (float) (clone $salesThisMonth)
            ->sum('total_amount');

        $eggsSoldThisMonth = (int) Sale::query()
            ->where('product_type', Sale::PRODUCT_EGG)
            ->whereDate('sold_on', '>=', $monthStart)
            ->whereDate('sold_on', '<=', $monthEnd)
            ->sum('quantity');

        $birdsSoldThisMonth = (int) Sale::query()
            ->where('product_type', Sale::PRODUCT_CHICK)
            ->whereDate('sold_on', '>=', $monthStart)
            ->whereDate('sold_on', '<=', $monthEnd)
            ->sum('quantity');

        // Monthly other expenses
        $otherExpensesThisMonth = (float) FarmTransaction::query()
            ->where('transaction_type', FarmTransaction::TYPE_EXPENSE)
            ->whereDate('transaction_on', '>=', $monthStart)
            ->whereDate('transaction_on', '<=', $monthEnd)
            ->sum('amount');

        // Dashboard expenses exclude feed and medicine purchases
        $totalExpensesThisMonth = $otherExpensesThisMonth;

        // Last 8 months analytics
        $monthlyAnalytics = collect(range(7, 0))
            ->map(function (int $monthsAgo) {
                $start = now()
                    ->startOfMonth()
                    ->subMonths($monthsAgo);

                $end = $start->copy()->endOfMonth();

                $startDate = $start->toDateString();
                $endDate = $end->toDateString();

                // Revenue
                $revenue = (float) Sale::query()
                    ->whereDate('sold_on', '>=', $startDate)
                    ->whereDate('sold_on', '<=', $endDate)
                    ->sum('total_amount');

                // Other expenses only
                $otherExpenses = (float) FarmTransaction::query()
                    ->where('transaction_type', FarmTransaction::TYPE_EXPENSE)
                    ->whereDate('transaction_on', '>=', $startDate)
                    ->whereDate('transaction_on', '<=', $endDate)
                    ->sum('amount');

                return [
                    'label' => $start->format('M'),
                    'year' => $start->format('Y'),
                    'revenue' => $revenue,
                    'expenses' => $otherExpenses,
                ];
            })
            ->values();

        // Chart scale
        $maxFinancialValue = max(
            1,
            (float) $monthlyAnalytics
                ->flatMap(fn ($item) => [
                    $item['revenue'],
                    $item['expenses'],
                ])
                ->max()
        );

        // Flock composition
        $flockTotal = $henStock + $roosterStock + $liveChicks;

        $henPercent = $flockTotal > 0
            ? ($henStock / $flockTotal) * 100
            : 0;

        $roosterPercent = $flockTotal > 0
            ? ($roosterStock / $flockTotal) * 100
            : 0;

        $chickPercent = $flockTotal > 0
            ? ($liveChicks / $flockTotal) * 100
            : 0;

        return [
            // Farm settings
            'farmSetting' => $this->farmSetting,

            // Production statistics
            'totalParentBirds' => $roosterStock + $henStock,
            'henStock' => $henStock,
            'roosterStock' => $roosterStock,

            'eggsToday' => (int) FlockDailyRecord::query()
                ->whereDate('recorded_on', $today)
                ->sum('eggs_collected'),

            'currentEggStock' => (int) EggStock::query()
                ->sum('good_available'),

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

            'liveChicks' => $liveChicks,
            'activeChickBatches' => $activeChickBatches,

            'hatchedThisMonth' => (int) IncubationBatch::query()
                ->where('status', IncubationBatch::STATUS_HATCHED)
                ->whereDate('actual_hatch_on', '>=', $monthStart)
                ->whereDate('actual_hatch_on', '<=', $monthEnd)
                ->sum('hatched_chicks'),

            // Financial statistics
            'salesRevenueThisMonth' => $salesRevenueThisMonth,
            'eggsSoldThisMonth' => $eggsSoldThisMonth,
            'birdsSoldThisMonth' => $birdsSoldThisMonth,

            'otherExpensesThisMonth' => $otherExpensesThisMonth,
            'totalExpensesThisMonth' => $totalExpensesThisMonth,

            'netProfitThisMonth' =>
                $salesRevenueThisMonth - $totalExpensesThisMonth,

            // Single monthly chart
            'monthlyAnalytics' => $monthlyAnalytics->all(),
            'maxFinancialValue' => $maxFinancialValue,

            // Flock composition
            'flockTotal' => $flockTotal,
            'henPercent' => $henPercent,
            'roosterPercent' => $roosterPercent,
            'chickPercent' => $chickPercent,
        ];
    }
}

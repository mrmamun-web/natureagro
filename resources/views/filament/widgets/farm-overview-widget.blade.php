<x-filament-widgets::widget>
    <div class="space-y-6">
        <section class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Farm overview</p>
                <h1 class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
                    {{ $farmSetting?->farm_name ?: 'Nature Agro' }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Today's operations and this month's totals · {{ now()->format('d M Y') }}
                </p>
            </div>
            <nav aria-label="Quick actions" class="flex flex-wrap gap-2">
                <a href="{{ \App\Filament\Resources\ParentFlocks\ParentFlockResource::getUrl('create') }}" class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                    Add parent flock
                </a>
                <a href="{{ \App\Filament\Resources\FlockDailyRecords\FlockDailyRecordResource::getUrl('create') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                    Record daily update
                </a>
                <a href="{{ \App\Filament\Resources\IncubationBatches\IncubationBatchResource::getUrl('create') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                    Start hatching
                </a>
                <a href="{{ \App\Filament\Resources\Sales\SaleResource::getUrl('create') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                    Record a sale
                </a>
                <a href="{{ \App\Filament\Resources\FarmTransactions\FarmTransactionResource::getUrl('create') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                    Other expense
                </a>
            </nav>
        </section>

        @php
            $productionStats = [
                ['Parent birds', number_format($totalParentBirds), number_format($henStock).' hens · '.number_format($roosterStock).' roosters'],
                ['Eggs collected today', number_format($eggsToday), number_format($eggsThisMonth).' collected this month'],
                ['Eggs currently incubating', number_format($eggsCurrentlyIncubating), number_format($activeIncubationBatches).' active batches'],
                ['Chicks hatched this month', number_format($hatchedThisMonth), number_format($liveChicks).' live across '.number_format($activeChickBatches).' active batches'],
            ];
            $totalExpensesThisMonth = $feedCostsThisMonth + $medicineCostsThisMonth + $otherExpensesThisMonth;
            $financeStats = [
                [
                    'Total sales',
                    '৳ '.number_format($salesRevenueThisMonth, 2),
                    number_format($eggsSoldThisMonth).' eggs · '.number_format($birdsSoldThisMonth).' batch chickens sold',
                ],
                [
                    'Total expenses',
                    '৳ '.number_format($totalExpensesThisMonth, 2),
                    'Feed purchases ৳ '.number_format($feedCostsThisMonth, 2).' · Medicine purchases ৳ '.number_format($medicineCostsThisMonth, 2).' · Other expenses ৳ '.number_format($otherExpensesThisMonth, 2),
                ],
                [
                    'Net profit',
                    '৳ '.number_format($salesRevenueThisMonth - $totalExpensesThisMonth, 2),
                    'Sales minus all recorded expenses',
                ],
            ];
        @endphp

        <section aria-label="Production summary" class="space-y-3">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Farm production</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($productionStats as [$label, $value, $hint])
                    <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</h3>
                        <p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $value }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section aria-label="Monthly sales and expenses" class="space-y-3">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Monthly sales & expenses</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($financeStats as [$label, $value, $hint])
                    <article @class([
                        'rounded-2xl border bg-white p-5 shadow-sm dark:bg-gray-900',
                        'border-emerald-200 dark:border-emerald-500/30' => $label === 'Net profit',
                        'border-gray-200 dark:border-white/10' => $label !== 'Net profit',
                    ])>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</h3>
                        <p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $value }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        @if ($totalParentBirds === 0)
            <p class="rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                No active parent flocks yet. Add a parent flock to begin tracking stock and egg production.
            </p>
        @endif
    </div>
</x-filament-widgets::widget>

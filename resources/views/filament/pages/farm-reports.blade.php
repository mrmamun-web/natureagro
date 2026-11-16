<x-filament-panels::page>
    <div class="space-y-6">
        <section class="farm-report-screen-only rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <form wire:submit.prevent="applyFilters" class="grid gap-4 sm:grid-cols-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Start month
                        <input type="month" wire:model="requestedStartMonth" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950">
                        @error('requestedStartMonth') <span class="mt-1 text-xs text-danger-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        End month
                        <input type="month" wire:model="requestedEndMonth" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950">
                        @error('requestedEndMonth') <span class="mt-1 text-xs text-danger-600">{{ $message }}</span> @enderror
                    </label>
                    <button type="submit" class="rounded-lg border border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-400/10">
                        Apply date range
                    </button>
                </form>
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="downloadXlsx" class="inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4" />
                        Download Excel (.xlsx)
                    </button>
                    <button type="button" wire:click="downloadCsv" class="inline-flex items-center gap-2 rounded-lg border border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-400/10">
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4" />
                        Download CSV
                    </button>
                    <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                        <x-filament::icon icon="heroicon-o-printer" class="h-4 w-4" />
                        Print / Save PDF
                    </button>
                </div>
            </div>
        </section>

        @php
            $cards = [
                ['Eggs collected', number_format($eggsCollected), 'Good '.number_format($goodEggs).' · damaged '.number_format($damagedEggs), 'heroicon-o-archive-box'],
                ['Eggs set for incubation', number_format($eggsSet), 'Chicks hatched '.number_format($chicksHatched), 'heroicon-o-sun'],
                ['Mortality', number_format($flockDeaths + $chickDeaths), 'Parent flock '.number_format($flockDeaths).' · batches '.number_format($chickDeaths), 'heroicon-o-heart'],
                ['Sales units', number_format($eggSales + $chickSales + $birdSales), 'Eggs '.number_format($eggSales).' · chicks '.number_format($chickSales).' · other birds '.number_format($birdSales), 'heroicon-o-shopping-cart'],
                ['Sales revenue', '৳ '.number_format($salesRevenue, 2), 'Outstanding on these invoices: ৳ '.number_format($salesDue, 2), 'heroicon-o-banknotes'],
                ['Cost of goods sold', '৳ '.number_format($costOfGoodsSold, 2), 'FIFO inventory cost recognized on sales', 'heroicon-o-receipt-percent'],
                ['Gross profit', $profitLossComplete ? '৳ '.number_format($grossProfit, 2) : 'Incomplete', 'Sales revenue less FIFO cost of goods sold', 'heroicon-o-chart-bar'],
                ['Inventory value', ($profitLossComplete ? '' : 'Known: ').'৳ '.number_format($openingInventoryValue, 2).' → ৳ '.number_format($closingInventoryValue, 2), 'Opening → closing FIFO inventory value', 'heroicon-o-archive-box'],
                ['Net profit / loss', $profitLossComplete ? '৳ '.number_format($netProfit, 2) : 'Incomplete', 'Requires valued stock and a breeder flock cost-allocation policy', 'heroicon-o-scale'],
                ['Other income / expenses', '৳ '.number_format($otherIncome, 2).' / ৳ '.number_format($otherExpenses, 2), 'Additional entries; excludes sales and inventory purchases', 'heroicon-o-scale'],
                ['Feed used / cost', number_format($feedConsumed, 3).' · ৳ '.number_format($feedCost, 2), 'FIFO direct costs capitalized to flock eggs or chick batches', 'heroicon-o-cube'],
                ['Medicine cost / vaccinations', '৳ '.number_format($medicineCost, 2).' · '.number_format($vaccinations), 'FIFO direct costs capitalized; vaccination count for the date range', 'heroicon-o-beaker'],
                ['Breeder cost allocation', $breederCostMethod === 'units_of_production' ? number_format($lifetimeGoodEggsPerHen).' eggs / hen default' : 'Not configured', 'Opening stock can use its own remaining egg estimate', 'heroicon-o-scale'],
                ['Supplier payables', '৳ '.number_format($supplierDues, 2), 'Current outstanding balance across inventory purchases', 'heroicon-o-truck'],
            ];
        @endphp

        <div id="farm-report-print" class="farm-report-print">
            <header class="farm-report-print-heading mb-5 hidden">
                <h1 class="text-2xl font-bold">Nature Agro — Farm Report</h1>
                <p class="mt-1 text-sm">
                    {{ \Illuminate\Support\Carbon::parse($dates['startDate'])->format('d M Y') }}
                    – {{ \Illuminate\Support\Carbon::parse($dates['endDate'])->format('d M Y') }}
                </p>
            </header>

            <div class="farm-report-grid grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($cards as [$label, $value, $hint, $icon])
                    <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</h2>
                                <p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $value }}</p>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
                            </div>
                            <span class="rounded-xl bg-emerald-50 p-3 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">
                                <x-filament::icon :icon="$icon" class="h-5 w-5" />
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>

            @if (! $profitLossComplete)
                <section class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-400/10 dark:text-amber-100">
                    <h2 class="font-semibold">Profit and loss is incomplete</h2>
                    <p class="mt-1">
                        {{ number_format($unvaluedStock) }} current stock balances still need opening valuation; {{ number_format($uncostedSales) }} sales in this date range have no recorded cost of goods sold.
                        @if ($breederCostAllocationPending)
                            Breeder-flock acquisition cost allocation is incomplete; egg and chick cost of goods sold may be understated.
                            @if ($remainingBreederCost > 0)
                                Live breeder flocks currently retain ৳ {{ number_format($remainingBreederCost, 2) }} of purchase value.
                            @endif
                        @endif
                        @if ($costTrackingStart && $dates['startDate'] < $costTrackingStart)
                            This period begins before FIFO cost tracking started on {{ \Illuminate\Support\Carbon::parse($costTrackingStart)->format('d M Y') }}.
                        @endif
                    </p>
                    @if ($unvaluedStock > 0)
                        <a href="{{ \App\Filament\Pages\OpeningStockValuations::getUrl() }}" class="mt-2 inline-flex font-semibold underline">Complete opening stock valuations</a>
                    @endif
                </section>
            @else
                <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">
                    Inventory purchases are valued as stock, not expenses. Feed and medicine usage is capitalized into egg or bird production; breeder acquisition cost uses units-of-production ({{ number_format($lifetimeGoodEggsPerHen) }} expected good eggs per hen by default, with an opening-stock override). FIFO cost is recognized when related stock is sold. Net profit includes recorded sales, other income and expenses, and mortality or total hatch-failure losses. Unrecorded overhead still needs to be entered.
                </p>
            @endif
        </div>
    </div>
</x-filament-panels::page>

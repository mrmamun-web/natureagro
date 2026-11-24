
<x-filament-panels::page>
    <div class="space-y-6">
        <section class="farm-report-screen-only rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <form wire:submit.prevent="applyFilters" class="grid gap-4 sm:grid-cols-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Start date
                        <input
                            type="date"
                            wire:model="requestedStartDate"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950"
                        >
                        @error('requestedStartDate')
                            <span class="mt-1 text-xs text-danger-600">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        End date
                        <input
                            type="date"
                            wire:model="requestedEndDate"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950"
                        >
                        @error('requestedEndDate')
                            <span class="mt-1 text-xs text-danger-600">{{ $message }}</span>
                        @enderror
                    </label>

                    <button
                        type="submit"
                        class="rounded-lg border border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-400/10"
                    >
                        Apply date range
                    </button>
                </form>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        wire:click="downloadXlsx"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800"
                    >
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4" />
                        Download Excel (.xlsx)
                    </button>

                    <button
                        type="button"
                        wire:click="downloadPdf"
                        class="inline-flex items-center gap-2 rounded-lg border border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-400/10"
                    >
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4" />
                        Download PDF
                    </button>

                    <button
                        type="button"
                        wire:click="downloadCsv"
                        class="inline-flex items-center gap-2 rounded-lg border border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-400/10"
                    >
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4" />
                        Download CSV
                    </button>

                    <button
                        type="button"
                        onclick="window.print()"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        <x-filament::icon icon="heroicon-o-printer" class="h-4 w-4" />
                        Print / Save PDF
                    </button>
                </div>
            </div>
        </section>

        @php
            $cards = [
                [
                    'Eggs collected',
                    number_format($eggsCollected),
                    'Good '.number_format($goodEggs).' · Damaged '.number_format($damagedEggs),
                    'heroicon-o-archive-box',
                ],
                [
                    'Eggs set for incubation',
                    number_format($eggsSet),
                    'Chicks hatched '.number_format($chicksHatched),
                    'heroicon-o-sun',
                ],
                [
                    'Eggs currently incubating',
                    number_format($eggsCurrentlyIncubating),
                    number_format($activeIncubationBatches).' active hatching batches',
                    'heroicon-o-sun',
                ],
                [
                    'Chicks surviving 7-day check',
                    number_format($chicksSurvivedAfterSevenDays),
                    'Recorded seven-day survival checks in this date range',
                    'heroicon-o-check-circle',
                ],
                [
                    'Mortality',
                    number_format($flockDeaths + $chickDeaths),
                    'Parent flock '.number_format($flockDeaths).' · Batches '.number_format($chickDeaths),
                    'heroicon-o-heart',
                ],
                [
                    'Sales units',
                    number_format($eggSales + $chickSales),
                    'Eggs '.number_format($eggSales).' · Hatch-batch chickens '.number_format($chickSales),
                    'heroicon-o-shopping-cart',
                ],
                [
                    'Sales revenue',
                    '৳ '.number_format($salesRevenue, 2),
                    'Outstanding on these invoices: ৳ '.number_format($salesDue, 2),
                    'heroicon-o-banknotes',
                ],
                [
                    'Feed expense',
                    '৳ '.number_format($feedCost, 2),
                    'Feed expenses recorded within this date range',
                    'heroicon-o-cube',
                ],
                [
                    'Medicine expense',
                    '৳ '.number_format($medicineCost, 2),
                    'Medicine expenses recorded within this date range',
                    'heroicon-o-beaker',
                ],
                [
                    'Other income / expenses',
                    '৳ '.number_format($otherIncome, 2).' / ৳ '.number_format($otherExpenses, 2),
                    'Other recorded entries, excluding feed and medicine expenses',
                    'heroicon-o-scale',
                ],
                [
                    'Net profit / loss',
                    '৳ '.number_format($netProfit, 2),
                    'Sales revenue minus feed, medicine and other recorded expenses',
                    'heroicon-o-scale',
                ],
                [
                    'Supplier payables',
                    '৳ '.number_format($supplierDues, 2),
                    'Current outstanding balance across inventory purchases',
                    'heroicon-o-truck',
                ],
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
                                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    {{ $label }}
                                </h2>

                                <p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">
                                    {{ $value }}
                                </p>

                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $hint }}
                                </p>
                            </div>

                            <span class="rounded-xl bg-emerald-50 p-3 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">
                                <x-filament::icon :icon="$icon" class="h-5 w-5" />
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>

            <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">
                Feed and medicine expenses are recorded directly as expense transactions.
                Net profit or loss is calculated from sales revenue minus feed expenses,
                medicine expenses and other recorded expenses. Supplier payables are shown
                separately and are not added again to this calculation.
            </p>
        </div>
    </div>
</x-filament-panels::page>

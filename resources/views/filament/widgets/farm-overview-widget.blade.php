<x-filament-widgets::widget>
    <div class="space-y-6">
        <section class="relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-950 via-emerald-900 to-teal-800 px-6 py-7 text-white shadow-xl sm:px-8 lg:px-10">
            <div class="pointer-events-none absolute -right-16 -top-28 -z-10 h-80 w-80 rounded-full bg-emerald-400/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-36 left-1/3 -z-10 h-72 w-72 rounded-full bg-teal-300/10 blur-3xl"></div>

            <div class="flex flex-col gap-7 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-200/80">
                            Nature Agro
                        </p>
                        <span class="rounded-full border border-emerald-200/20 bg-emerald-300/10 px-2.5 py-1 text-[11px] font-medium text-emerald-100">
                            Farm overview
                        </span>
                    </div>
                    <h1 class="truncate text-2xl font-bold tracking-tight sm:text-3xl">
                        {{ $farmSetting?->farm_name ?: 'Welcome to Nature Agro' }}
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-emerald-100/75 sm:text-base">
                        {{ $farmSetting?->description ?: 'Your farm operations, organized in one place.' }}
                    </p>
                    <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-emerald-100/80">
                        @if ($farmSetting?->owner_name)
                            <span class="inline-flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-user" class="h-4 w-4" />
                                {{ $farmSetting->owner_name }}
                            </span>
                        @endif
                        @if ($farmSetting?->phone_number)
                            <span class="inline-flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-phone" class="h-4 w-4" />
                                {{ $farmSetting->phone_number }}
                            </span>
                        @endif
                        <span class="inline-flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-calendar-days" class="h-4 w-4" />
                            {{ now()->format('D, d M Y') }}
                        </span>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap gap-3">
                    <a
                        href="{{ \App\Filament\Resources\ParentFlocks\ParentFlockResource::getUrl('create') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-emerald-950 shadow-sm transition hover:bg-emerald-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                    >
                        <x-filament::icon icon="heroicon-o-plus" class="h-4 w-4" />
                        Add parent flock
                    </a>
                    <a
                        href="{{ \App\Filament\Resources\FlockDailyRecords\FlockDailyRecordResource::getUrl('create') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/5 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                    >
                        <x-filament::icon icon="heroicon-o-sparkles" class="h-4 w-4" />
                        Record today
                    </a>
                    <a
                        href="{{ \App\Filament\Pages\FarmSettings::getUrl() }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/5 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                    >
                        <x-filament::icon icon="heroicon-o-cog-6-tooth" class="h-4 w-4" />
                        Farm settings
                    </a>
                </div>
            </div>
        </section>

        <section aria-labelledby="farm-stats-heading">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 id="farm-stats-heading" class="text-lg font-bold tracking-tight text-gray-950 dark:text-white">
                        Farm at a glance
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Live flock, egg and chick counts from your records.
                    </p>
                </div>
                <a
                    href="{{ \App\Filament\Resources\ParentFlocks\ParentFlockResource::getUrl('index') }}"
                    class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-700 transition hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
                >
                    Manage parent flocks
                    <x-filament::icon icon="heroicon-o-arrow-right" class="h-4 w-4" />
                </a>
            </div>

            @php
                $stats = [
                    [
                        'label' => 'Parent birds',
                        'value' => $totalParentBirds,
                        'hint' => 'Hens and roosters in active flocks',
                        'icon' => 'heroicon-o-sparkles',
                        'accent' => 'bg-gradient-to-r from-emerald-400 to-emerald-600',
                        'iconTone' => 'bg-emerald-50 text-emerald-700 ring-emerald-100 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20',
                    ],
                    [
                        'label' => 'Eggs collected today',
                        'value' => $eggsToday,
                        'hint' => number_format($eggsThisMonth) . ' collected this month',
                        'icon' => 'heroicon-o-archive-box',
                        'accent' => 'bg-gradient-to-r from-lime-400 to-lime-600',
                        'iconTone' => 'bg-lime-50 text-lime-700 ring-lime-100 dark:bg-lime-400/10 dark:text-lime-300 dark:ring-lime-400/20',
                    ],
                    [
                        'label' => 'Good eggs available',
                        'value' => $availableEggs,
                        'hint' => number_format($reservedEggs) . ' eggs reserved for hatching',
                        'icon' => 'heroicon-o-queue-list',
                        'accent' => 'bg-gradient-to-r from-amber-400 to-amber-600',
                        'iconTone' => 'bg-amber-50 text-amber-700 ring-amber-100 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20',
                    ],
                    [
                        'label' => 'Live chicks',
                        'value' => $liveChicks,
                        'hint' => number_format($activeChickBatches) . ' active batches',
                        'icon' => 'heroicon-o-rectangle-stack',
                        'accent' => 'bg-gradient-to-r from-teal-400 to-teal-600',
                        'iconTone' => 'bg-teal-50 text-teal-700 ring-teal-100 dark:bg-teal-400/10 dark:text-teal-300 dark:ring-teal-400/20',
                    ],
                    [
                        'label' => 'Hatched this month',
                        'value' => $hatchedThisMonth,
                        'hint' => 'Successful hatch outcomes',
                        'icon' => 'heroicon-o-sun',
                        'accent' => 'bg-gradient-to-r from-cyan-400 to-cyan-600',
                        'iconTone' => 'bg-cyan-50 text-cyan-700 ring-cyan-100 dark:bg-cyan-400/10 dark:text-cyan-300 dark:ring-cyan-400/20',
                    ],
                    [
                        'label' => 'Parent flocks',
                        'value' => $activeParentFlocks,
                        'hint' => 'Currently active flocks',
                        'icon' => 'heroicon-o-building-office-2',
                        'accent' => 'bg-gradient-to-r from-violet-400 to-violet-600',
                        'iconTone' => 'bg-violet-50 text-violet-700 ring-violet-100 dark:bg-violet-400/10 dark:text-violet-300 dark:ring-violet-400/20',
                    ],
                    [
                        'label' => 'Eggs sold today',
                        'value' => $eggSalesToday,
                        'hint' => 'Egg sales recorded today',
                        'icon' => 'heroicon-o-shopping-cart',
                        'accent' => 'bg-gradient-to-r from-orange-400 to-orange-600',
                        'iconTone' => 'bg-orange-50 text-orange-700 ring-orange-100 dark:bg-orange-400/10 dark:text-orange-300 dark:ring-orange-400/20',
                    ],
                    [
                        'label' => 'Birds sold this month',
                        'value' => $poultrySalesThisMonth,
                        'hint' => number_format($chickSalesThisMonth).' chicks sold separately',
                        'icon' => 'heroicon-o-arrow-trending-up',
                        'accent' => 'bg-gradient-to-r from-blue-400 to-blue-600',
                        'iconTone' => 'bg-blue-50 text-blue-700 ring-blue-100 dark:bg-blue-400/10 dark:text-blue-300 dark:ring-blue-400/20',
                    ],
                    [
                        'label' => 'Sales revenue this month',
                        'value' => $salesRevenueThisMonth,
                        'format' => 'currency',
                        'hint' => 'Total value of recorded sales',
                        'icon' => 'heroicon-o-banknotes',
                        'accent' => 'bg-gradient-to-r from-green-400 to-green-600',
                        'iconTone' => 'bg-green-50 text-green-700 ring-green-100 dark:bg-green-400/10 dark:text-green-300 dark:ring-green-400/20',
                    ],
                    [
                        'label' => 'Customer dues',
                        'value' => $outstandingDues,
                        'format' => 'currency',
                        'hint' => 'Outstanding balance on all invoices',
                        'icon' => 'heroicon-o-credit-card',
                        'accent' => 'bg-gradient-to-r from-rose-400 to-rose-600',
                        'iconTone' => 'bg-rose-50 text-rose-700 ring-rose-100 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/20',
                    ],
                    [
                        'label' => 'Other expenses this month',
                        'value' => $otherExpensesThisMonth,
                        'format' => 'currency',
                        'hint' => 'Recorded operating expenses; feed and medicine usage tracked separately',
                        'icon' => 'heroicon-o-receipt-percent',
                        'accent' => 'bg-gradient-to-r from-pink-400 to-pink-600',
                        'iconTone' => 'bg-pink-50 text-pink-700 ring-pink-100 dark:bg-pink-400/10 dark:text-pink-300 dark:ring-pink-400/20',
                    ],
                    [
                        'label' => 'Feed consumed this month',
                        'value' => $feedCostsThisMonth,
                        'format' => 'currency',
                        'hint' => 'Cost of feed actually issued to flocks and batches',
                        'icon' => 'heroicon-o-cube',
                        'accent' => 'bg-gradient-to-r from-yellow-400 to-yellow-600',
                        'iconTone' => 'bg-yellow-50 text-yellow-700 ring-yellow-100 dark:bg-yellow-400/10 dark:text-yellow-300 dark:ring-yellow-400/20',
                    ],
                    [
                        'label' => 'Medicine used this month',
                        'value' => $medicineCostsThisMonth,
                        'format' => 'currency',
                        'hint' => 'FIFO cost of medicine actually used',
                        'icon' => 'heroicon-o-beaker',
                        'accent' => 'bg-gradient-to-r from-purple-400 to-purple-600',
                        'iconTone' => 'bg-purple-50 text-purple-700 ring-purple-100 dark:bg-purple-400/10 dark:text-purple-300 dark:ring-purple-400/20',
                    ],
                    [
                        'label' => 'Supplier payables',
                        'value' => $supplierDues,
                        'format' => 'currency',
                        'hint' => 'Unpaid balance on inventory purchases',
                        'icon' => 'heroicon-o-truck',
                        'accent' => 'bg-gradient-to-r from-slate-400 to-slate-600',
                        'iconTone' => 'bg-slate-50 text-slate-700 ring-slate-100 dark:bg-slate-400/10 dark:text-slate-300 dark:ring-slate-400/20',
                    ],
                ];
            @endphp

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($stats as $stat)
                    <article class="group relative overflow-hidden rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-gray-900">
                        <div class="absolute inset-x-0 top-0 h-1 {{ $stat['accent'] }}"></div>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                                <p class="mt-3 text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
                                    {{ ($stat['format'] ?? null) === 'currency' ? '৳ '.number_format((float) $stat['value'], 2) : number_format($stat['value']) }}
                                </p>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $stat['hint'] }}</p>
                            </div>
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ring-1 {{ $stat['iconTone'] }}">
                                <x-filament::icon :icon="$stat['icon']" class="h-5 w-5" />
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="grid gap-5 lg:grid-cols-2 2xl:grid-cols-3">
            <div class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-gray-950 dark:text-white">Low-stock alerts</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Usable stock at or below your product threshold</p>
                    </div>
                    <a href="{{ \App\Filament\Resources\SupplyProducts\SupplyProductResource::getUrl('index') }}" class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Inventory</a>
                </div>
                <div class="mt-4 divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($lowStockProducts as $product)
                        <div class="flex items-center justify-between gap-3 py-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $product->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($product->type) }} · minimum {{ number_format((float) $product->minimum_stock, 3) }} {{ $product->unit }}</p>
                            </div>
                            <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-400/10 dark:text-rose-300">
                                {{ number_format($product->usable_stock_quantity, 3) }} {{ $product->unit }}
                            </span>
                        </div>
                    @empty
                        <p class="py-5 text-sm text-gray-500 dark:text-gray-400">No products are below their configured threshold.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-gray-950 dark:text-white">Upcoming vaccinations</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Next-dose reminders for the coming seven days</p>
                    </div>
                    <a href="{{ \App\Filament\Resources\SupplyUsages\SupplyUsageResource::getUrl('index') }}" class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Health records</a>
                </div>
                <div class="mt-4 divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($upcomingVaccinations as $vaccination)
                        <div class="flex items-center justify-between gap-3 py-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $vaccination->product->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $vaccination->parentFlock?->name ?? $vaccination->chickBatch?->batch_code }}</p>
                            </div>
                            <span class="text-xs font-semibold text-amber-700 dark:text-amber-300">{{ $vaccination->next_dose_on->format('d M Y') }}</span>
                        </div>
                    @empty
                        <p class="py-5 text-sm text-gray-500 dark:text-gray-400">No vaccination reminders due in the next seven days.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-gray-950 dark:text-white">Medicine expiry</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Medicine lots expiring in the next 30 days</p>
                    </div>
                    <a href="{{ \App\Filament\Resources\SupplyPurchases\SupplyPurchaseResource::getUrl('index') }}" class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Purchases</a>
                </div>
                <div class="mt-4 divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($expiringMedicineLots as $lot)
                        <div class="flex items-center justify-between gap-3 py-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $lot->product->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format((float) $lot->remaining_quantity, 3) }} {{ $lot->product->unit }} remaining</p>
                            </div>
                            <span class="text-xs font-semibold text-amber-700 dark:text-amber-300">{{ $lot->expires_on->format('d M Y') }}</span>
                        </div>
                    @empty
                        <p class="py-5 text-sm text-gray-500 dark:text-gray-400">No medicine lots expire in the next 30 days.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-gray-950 dark:text-white">Egg collection trend</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Daily eggs collected over the last 14 days</p>
                </div>
                <span class="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">
                    {{ number_format($eggsThisMonth) }} this month
                </span>
            </div>

            @php
                $maxEggCount = max(1, collect($dailyEggTrend)->max('count'));
            @endphp

            <div class="mt-6 grid h-40 items-end gap-2 sm:gap-3" style="grid-template-columns: repeat(14, minmax(0, 1fr))">
                @foreach ($dailyEggTrend as $day)
                    @php
                        $barHeight = $day['count'] > 0 ? max(8, ($day['count'] / $maxEggCount) * 100) : 3;
                    @endphp
                    <div class="group flex h-full flex-col items-center justify-end gap-2" title="{{ $day['date'] }}: {{ number_format($day['count']) }} eggs">
                        <div class="w-full rounded-t-md bg-emerald-500/85 transition-colors group-hover:bg-emerald-400" style="height: {{ $barHeight }}%"></div>
                        <span class="text-[10px] tabular-nums text-gray-400">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
            <section class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900 xl:col-span-2">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-base font-bold text-gray-950 dark:text-white">Parent flock composition</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Current hens and roosters</p>
                    </div>
                    <span class="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">
                        {{ number_format($totalParentBirds) }} birds
                    </span>
                </div>

                @if ($totalParentBirds > 0)
                    <div class="mt-7 space-y-5">
                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <span class="inline-flex items-center gap-2 font-medium text-gray-700 dark:text-gray-200">
                                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                    Hens
                                </span>
                                <span class="font-semibold text-gray-950 dark:text-white">
                                    {{ number_format($henStock) }}
                                    <span class="ml-1 text-xs font-normal text-gray-500 dark:text-gray-400">{{ $henPercentage }}%</span>
                                </span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all" style="width: {{ $henPercentage }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <span class="inline-flex items-center gap-2 font-medium text-gray-700 dark:text-gray-200">
                                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                                    Roosters
                                </span>
                                <span class="font-semibold text-gray-950 dark:text-white">
                                    {{ number_format($roosterStock) }}
                                    <span class="ml-1 text-xs font-normal text-gray-500 dark:text-gray-400">{{ $roosterPercentage }}%</span>
                                </span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-orange-400 transition-all" style="width: {{ $roosterPercentage }}%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-7 flex items-center gap-2 border-t border-gray-100 pt-4 text-xs leading-5 text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-o-information-circle" class="h-4 w-4 shrink-0" />
                        Stock is calculated from active parent flock records only.
                    </div>
                @else
                    <div class="mt-6 rounded-xl border border-dashed border-gray-300 px-5 py-8 text-center dark:border-gray-700">
                        <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                            <x-filament::icon icon="heroicon-o-chart-pie" class="h-5 w-5" />
                        </span>
                        <p class="mt-3 text-sm font-semibold text-gray-800 dark:text-gray-200">No active stock yet</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Add a parent flock to see the stock breakdown.</p>
                    </div>
                @endif
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900 xl:col-span-3">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-white/10">
                    <div>
                        <h2 class="text-base font-bold text-gray-950 dark:text-white">Recently added poultry</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Latest records in your farm inventory</p>
                    </div>
                    <a
                        href="{{ \App\Filament\Resources\Poultries\PoultryResource::getUrl('index') }}"
                        class="text-sm font-semibold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400"
                    >
                        See all
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-left text-sm">
                        <thead class="bg-gray-50/80 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Poultry</th>
                                <th class="px-4 py-3 font-semibold">Type</th>
                                <th class="px-4 py-3 text-right font-semibold">Stock</th>
                                <th class="px-5 py-3 text-right font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse ($latestPoultries as $poultry)
                                <tr class="transition hover:bg-gray-50/70 dark:hover:bg-white/[0.03]">
                                    <td class="px-5 py-3.5">
                                        <a
                                            href="{{ \App\Filament\Resources\Poultries\PoultryResource::getUrl('view', ['record' => $poultry]) }}"
                                            class="font-semibold text-gray-900 hover:text-emerald-700 dark:text-white dark:hover:text-emerald-400"
                                        >
                                            {{ $poultry->name }}
                                        </a>
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $poultry->poultry_code }}</p>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $poultry->type === \App\Models\Poultry::TYPE_CHICKEN ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300' }}">
                                            {{ ucfirst($poultry->type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-semibold tabular-nums text-gray-900 dark:text-white">
                                        {{ number_format($poultry->current_quantity) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ $poultry->status === \App\Models\Poultry::STATUS_ACTIVE ? 'text-emerald-700 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $poultry->status === \App\Models\Poultry::STATUS_ACTIVE ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                            {{ ucfirst($poultry->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center">
                                        <x-filament::icon icon="heroicon-o-inbox" class="mx-auto h-8 w-8 text-gray-400" />
                                        <p class="mt-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Your poultry list is empty</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Create your first record to start tracking farm stock.</p>
                                        <a
                                            href="{{ \App\Filament\Resources\Poultries\PoultryResource::getUrl('create') }}"
                                            class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400"
                                        >
                                            <x-filament::icon icon="heroicon-o-plus" class="h-4 w-4" />
                                            Add poultry
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <section class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900 sm:p-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-gray-950 dark:text-white">Management modules</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Your dashboard only shows operational data that is currently tracked in the system.
                    </p>
                </div>
                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">
                    More modules planned
                </span>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    ['Batch mortality & transfers', 'heroicon-o-arrows-right-left', \App\Filament\Resources\ChickBatches\ChickBatchResource::getUrl('index')],
                    ['Sales & customer dues', 'heroicon-o-shopping-cart', \App\Filament\Resources\Sales\SaleResource::getUrl('index')],
                    ['Feed & medicine inventory', 'heroicon-o-beaker', \App\Filament\Resources\SupplyProducts\SupplyProductResource::getUrl('index')],
                    ['Other income & expenses', 'heroicon-o-banknotes', \App\Filament\Resources\FarmTransactions\FarmTransactionResource::getUrl('index')],
                    ['Date-filtered farm reports', 'heroicon-o-chart-bar', \App\Filament\Pages\FarmReports::getUrl()],
                ] as [$module, $icon, $url])
                    <div class="flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/40">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-gray-500 shadow-sm dark:bg-gray-800 dark:text-gray-300">
                            <x-filament::icon :icon="$icon" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $module }}</p>
                            @if ($url)
                                <a href="{{ $url }}" class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400">
                                    Open module <x-filament::icon icon="heroicon-o-arrow-right" class="h-3 w-3" />
                                </a>
                            @else
                                <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">Not tracked yet</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-filament-widgets::widget>

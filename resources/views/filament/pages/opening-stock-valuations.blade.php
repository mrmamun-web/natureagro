<x-filament-panels::page>
    <div class="space-y-6">
        <p class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-400/10 dark:text-amber-100">
            Enter the current cost per unit for each unvalued opening balance. This one-time valuation is required before that stock can be sold, transferred, or used to capitalize production costs. It does not rewrite earlier transactions.
        </p>

        @php
            $sections = [
                ['title' => 'Chick batches', 'items' => $chickBatches, 'input' => 'chick_batch', 'method' => 'valueChickBatch'],
                ['title' => 'General poultry', 'items' => $poultries, 'input' => 'poultry', 'method' => 'valuePoultry'],
                ['title' => 'Available eggs', 'items' => $eggStocks, 'input' => 'egg_stock', 'method' => 'valueEggStock'],
                ['title' => 'Eggs in incubation', 'items' => $incubationBatches, 'input' => 'incubation_batch', 'method' => 'valueIncubationBatch'],
            ];
        @endphp

        @foreach ($sections as $section)
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <header class="border-b border-gray-200 px-5 py-4 dark:border-white/10">
                    <h2 class="font-semibold text-gray-950 dark:text-white">{{ $section['title'] }}</h2>
                </header>
                @if (count($section['items']))
                    <div class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($section['items'] as $item)
                            <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-medium text-gray-950 dark:text-white">
                                        {{ $item['name'] }}
                                    </p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Current quantity: {{ number_format((float) $item['quantity'], 3) }}
                                    </p>
                                </div>
                                <div class="flex items-start gap-2">
                                    <label class="sr-only" for="opening-cost-{{ $section['input'] }}-{{ $item['id'] }}-{{ $item['gender'] ?? 'all' }}">
                                        Current unit cost in BDT
                                    </label>
                                    <input id="opening-cost-{{ $section['input'] }}-{{ $item['id'] }}" type="number" min="0" step="0.01" wire:model="openingCosts.{{ $section['input'] }}.{{ $item['id'] }}" placeholder="Unit cost (BDT)" class="w-40 rounded-lg border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950">
                                    @error('openingCosts.'.$section['input'].'.'.$item['id']) <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                                    <button type="button" wire:click="{{ $section['method'] }}({{ $item['id'] }})" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Save</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="p-5 text-sm text-gray-500 dark:text-gray-400">No unvalued stock.</p>
                @endif
            </section>
        @endforeach
    </div>
</x-filament-panels::page>

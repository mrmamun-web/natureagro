
<x-filament-widgets::widget>
    @php
        $currency = $farmSetting?->currency ?: 'BDT';
        $currencySymbol = $currency === 'BDT' ? '৳' : $currency;

        $productionStats = [
            [
                'label' => 'Parent Birds',
                'value' => $totalParentBirds,
                'hint' => number_format($henStock) . ' hens · ' . number_format($roosterStock) . ' roosters',
                'icon' => '🐔',
                'color' => 'green',
            ],
            [
                'label' => 'Available Egg Stock',
                'value' => $currentEggStock,
                'hint' => 'Good eggs available for sale',
                'icon' => '🥚',
                'color' => 'amber',
            ],
            [
                'label' => 'Incubating Eggs',
                'value' => $eggsCurrentlyIncubating,
                'hint' => number_format($activeIncubationBatches) . ' active batches',
                'icon' => '🐣',
                'color' => 'blue',
            ],
            [
                'label' => 'Eggs Collected Today',
                'value' => $eggsToday,
                'hint' => number_format($eggsThisMonth) . ' collected this month',
                'icon' => '📦',
                'color' => 'green',
            ],
            [
                'label' => 'Chicks Hatched This Month',
                'value' => $hatchedThisMonth,
                'hint' => number_format($liveChicks) . ' live chicks · ' . number_format($activeChickBatches) . ' batches',
                'icon' => '🐤',
                'color' => 'blue',
            ],
        ];

        $financeStats = [
            [
                'label' => 'Total Sales',
                'value' => $currencySymbol . ' ' . number_format($salesRevenueThisMonth, 2),
                'hint' => number_format($eggsSoldThisMonth) . ' eggs · ' . number_format($birdsSoldThisMonth) . ' chicks sold',
                'icon' => '💰',
                'color' => 'green',
            ],
            [
                'label' => 'Total Expenses',
                'value' => $currencySymbol . ' ' . number_format($totalExpensesThisMonth, 2),
                'hint' => 'Feed ' . $currencySymbol . ' ' . number_format($feedCostsThisMonth, 2) .
                    ' · Medicine ' . $currencySymbol . ' ' . number_format($medicineCostsThisMonth, 2) .
                    ' · Other ' . $currencySymbol . ' ' . number_format($otherExpensesThisMonth, 2),
                'icon' => '💸',
                'color' => 'amber',
            ],
            [
                'label' => 'Net Profit',
                'value' => $currencySymbol . ' ' . number_format($netProfitThisMonth, 2),
                'hint' => 'Sales minus recorded expenses',
                'icon' => '📈',
                'color' => $netProfitThisMonth >= 0 ? 'green' : 'red',
            ],
        ];
    @endphp

    <div class="farm-overview space-y-6">

        {{-- =========================================
             FARM HEADER
        ========================================= --}}

        <header class="farm-header">

            <div class="farm-header-top">

                <div class="farm-header-content">

                    <div class="farm-header-eyebrow">
                        <span class="farm-header-eyebrow-dot"></span>
                        FARM MANAGEMENT SYSTEM
                    </div>

                    <h1>
                        {{ $farmSetting?->farm_name ?: 'Nature Agro' }}
                    </h1>

                    <p class="farm-header-description">
                        {{ $farmSetting?->description ?: 'Manage your poultry operations, production, inventory and finances from one place.' }}
                    </p>

                    <div class="farm-header-status">
                        <span class="farm-status-indicator"></span>
                        System Overview
                        <span class="farm-status-divider"></span>
                        <span>{{ now()->format('l, d F Y') }}</span>
                    </div>

                </div>

                <div class="farm-header-report">

                    <span class="farm-report-label">REPORTING PERIOD</span>

                    <strong>{{ now()->format('F Y') }}</strong>

                    <span class="farm-report-date">
                        Updated {{ now()->format('d M Y') }}
                    </span>

                </div>

            </div>

            <div class="farm-header-divider"></div>

            {{-- ANIMATED CHICKEN --}}
            <div class="farm-chicken-track" aria-hidden="true">

                <div class="farm-chicken-walker">
                    <span class="farm-chicken-body">🐔</span>
                    <span class="farm-chicken-shadow"></span>
                </div>

            </div>

            {{-- FARM INFORMATION --}}
            <div class="farm-header-information">

                <div class="farm-information-item">

                    <span class="farm-information-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 21v-2a8 8 0 0 1 16 0v2"></path>
                        </svg>
                    </span>

                    <div>
                        <small>FARM OWNER</small>
                        <strong>{{ $farmSetting?->owner_name ?: 'Not provided' }}</strong>
                    </div>

                </div>

                <div class="farm-information-item">

                    <span class="farm-information-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2
                                19.79 19.79 0 0 1-8.63-3.07
                                19.5 19.5 0 0 1-6-6
                                19.79 19.79 0 0 1-3.07-8.67
                                A2 2 0 0 1 4.11 2h3
                                a2 2 0 0 1 2 1.72
                                c.12.96.36 1.9.72 2.8
                                a2 2 0 0 1-.45 2.11L8.11 9.9
                                a16 16 0 0 0 6 6l1.27-1.27
                                a2 2 0 0 1 2.11-.45
                                c.9.36 1.84.6 2.8.72
                                A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                    </span>

                    <div>
                        <small>CONTACT NUMBER</small>
                        <strong>{{ $farmSetting?->phone_number ?: 'Not provided' }}</strong>
                    </div>

                </div>

                <div class="farm-information-item">

                    <span class="farm-information-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"></path>
                            <circle cx="12" cy="10" r="2.5"></circle>
                        </svg>
                    </span>

                    <div>
                        <small>FARM LOCATION</small>
                        <strong>{{ $farmSetting?->address ?: 'Not provided' }}</strong>
                    </div>

                </div>

                <div class="farm-information-item">

                    <span class="farm-information-icon currency-icon">৳</span>

                    <div>
                        <small>CURRENCY</small>
                        <strong>{{ $currency }}</strong>
                    </div>

                </div>

            </div>

        </header>


        {{-- =========================================
             QUICK ACTIONS
        ========================================= --}}

        <section class="farm-quick-section">

            <div class="farm-section-heading">
                <div>
                    <h2>Quick Actions</h2>
                    <p>Manage your daily farm operations</p>
                </div>
            </div>

            <nav aria-label="Quick actions" class="farm-actions">

                <a
                    href="{{ \App\Filament\Resources\ParentFlocks\ParentFlockResource::getUrl('create') }}"
                    class="farm-action farm-action-primary"
                >
                    <span class="farm-action-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 5v14M5 12h14"></path>
                        </svg>
                    </span>
                    <span class="farm-action-label">Add Parent Flock</span>
                    <span class="farm-action-arrow">→</span>
                </a>

                <a
                    href="{{ \App\Filament\Resources\FlockDailyRecords\FlockDailyRecordResource::getUrl('create') }}"
                    class="farm-action"
                >
                    <span class="farm-action-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="4" y="3" width="16" height="18" rx="2"></rect>
                            <path d="M8 8h8M8 12h8M8 16h5"></path>
                        </svg>
                    </span>
                    <span class="farm-action-label">Daily Update</span>
                    <span class="farm-action-arrow">→</span>
                </a>

                <a
                    href="{{ \App\Filament\Resources\IncubationBatches\IncubationBatchResource::getUrl('create') }}"
                    class="farm-action"
                >
                    <span class="farm-action-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 3c-4 0-7 7-7 11a7 7 0 0 0 14 0c0-4-3-11-7-11Z"></path>
                        </svg>
                    </span>
                    <span class="farm-action-label">Start Hatching</span>
                    <span class="farm-action-arrow">→</span>
                </a>

                <a
                    href="{{ \App\Filament\Resources\Sales\SaleResource::getUrl('create') }}"
                    class="farm-action"
                >
                    <span class="farm-action-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 3h2l2.4 12.2a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 8H6"></path>
                            <circle cx="10" cy="21" r="1"></circle>
                            <circle cx="18" cy="21" r="1"></circle>
                        </svg>
                    </span>
                    <span class="farm-action-label">Record Sale</span>
                    <span class="farm-action-arrow">→</span>
                </a>

                <a
                    href="{{ \App\Filament\Resources\FarmTransactions\FarmTransactionResource::getUrl('create') }}"
                    class="farm-action"
                >
                    <span class="farm-action-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="15" rx="2"></rect>
                            <path d="M3 10h18M8 15h3"></path>
                        </svg>
                    </span>
                    <span class="farm-action-label">Other Expense</span>
                    <span class="farm-action-arrow">→</span>
                </a>

            </nav>

        </section>


        {{-- =========================================
             FARM PRODUCTION
        ========================================= --}}

        <section class="farm-section">

            <div class="farm-section-heading">

                <div>
                    <h2>Farm Production</h2>
                    <p>Current production and livestock statistics</p>
                </div>

                <span class="farm-section-badge">LIVE DATA</span>

            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">

                @foreach ($productionStats as $stat)

                    <article class="farm-stat-card">

                        <div class="farm-card-top">

                            <span class="farm-card-label">{{ $stat['label'] }}</span>

                            <span class="farm-card-icon">{{ $stat['icon'] }}</span>

                        </div>

                        <strong class="farm-stat-value">
                            {{ number_format($stat['value']) }}
                        </strong>

                        <p class="farm-stat-hint">{{ $stat['hint'] }}</p>

                        <div class="farm-card-bottom {{ $stat['color'] }}">
                            <span></span>
                            Database statistics
                        </div>

                    </article>

                @endforeach

            </div>

        </section>


        {{-- =========================================
             FINANCIAL OVERVIEW
        ========================================= --}}

        <section class="farm-section">

            <div class="farm-section-heading">
                <div>
                    <h2>Monthly Financial Overview</h2>
                    <p>Financial summary for {{ now()->format('F Y') }}</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

                @foreach ($financeStats as $stat)

                    <article class="farm-finance-card {{ $stat['color'] }}">

                        <div class="farm-finance-top">

                            <div>
                                <p>{{ $stat['label'] }}</p>
                                <strong>{{ $stat['value'] }}</strong>
                            </div>

                            <span>{{ $stat['icon'] }}</span>

                        </div>

                        <p class="farm-finance-hint">{{ $stat['hint'] }}</p>

                    </article>

                @endforeach

            </div>

        </section>


        {{-- =========================================
             FARM ANALYTICS
        ========================================= --}}

        <section class="farm-section">

            <div class="farm-section-heading">

                <div>
                    <h2>Farm Analytics</h2>
                    <p>Understand your farm's financial performance</p>
                </div>

                <span class="farm-live-status">
                    <span></span>
                    Live Database
                </span>

            </div>

            <div class="farm-chart-card">

                <div class="farm-chart-heading">

                    <div>
                        <span class="farm-chart-eyebrow">FINANCIAL ANALYTICS</span>
                        <h3>Monthly Financial Performance</h3>
                        <p>Revenue vs Expenses · Last 8 Months</p>
                    </div>

                    <div class="farm-chart-total">
                        <span>THIS MONTH REVENUE</span>
                        <strong>
                            {{ $currencySymbol }}
                            {{ number_format($salesRevenueThisMonth, 2) }}
                        </strong>
                    </div>

                </div>

                <div class="farm-chart-legend">

                    <span>
                        <i class="farm-legend-dot revenue-dot"></i>
                        Revenue
                    </span>

                    <span>
                        <i class="farm-legend-dot expense-dot"></i>
                        Expenses
                    </span>

                </div>

                <div class="farm-chart-scene">

                    <div class="farm-chart-grid">
                        <div class="farm-grid-line"></div>
                        <div class="farm-grid-line"></div>
                        <div class="farm-grid-line"></div>
                        <div class="farm-grid-line"></div>
                    </div>

                    <div class="farm-chart-columns">

                        @foreach ($monthlyAnalytics as $month)

                            @php
                                $revenueHeight = min(
                                    100,
                                    max(0, ($month['revenue'] / max(1, $maxFinancialValue)) * 100)
                                );

                                $expenseHeight = min(
                                    100,
                                    max(0, ($month['expenses'] / max(1, $maxFinancialValue)) * 100)
                                );

                                $shortRevenue = $month['revenue'] >= 1000000
                                    ? number_format($month['revenue'] / 1000000, 1) . 'M'
                                    : (
                                        $month['revenue'] >= 1000
                                            ? number_format($month['revenue'] / 1000, 1) . 'K'
                                            : number_format($month['revenue'], 0)
                                    );

                                $shortExpenses = $month['expenses'] >= 1000000
                                    ? number_format($month['expenses'] / 1000000, 1) . 'M'
                                    : (
                                        $month['expenses'] >= 1000
                                            ? number_format($month['expenses'] / 1000, 1) . 'K'
                                            : number_format($month['expenses'], 0)
                                    );
                            @endphp

                            <div class="farm-chart-column">

                                <div class="farm-chart-values">
                                    <span class="revenue-value">{{ $shortRevenue }}</span>
                                    <span class="expense-value">{{ $shortExpenses }}</span>
                                </div>

                                <div class="farm-bars-wrapper">

                                    <div class="farm-chart-track">
                                        <div
                                            class="farm-chart-bar revenue-bar"
                                            style="--bar-height: {{ $revenueHeight }}%;"
                                            title="Revenue: {{ $currencySymbol }} {{ number_format($month['revenue'], 2) }}"
                                        ></div>
                                    </div>

                                    <div class="farm-chart-track">
                                        <div
                                            class="farm-chart-bar expense-bar"
                                            style="--bar-height: {{ $expenseHeight }}%;"
                                            title="Expenses: {{ $currencySymbol }} {{ number_format($month['expenses'], 2) }}"
                                        ></div>
                                    </div>

                                </div>

                                <span class="farm-chart-month">{{ $month['label'] }}</span>
                                <span class="farm-chart-year">{{ $month['year'] }}</span>

                            </div>

                        @endforeach

                    </div>

                </div>

                <div class="farm-chart-footer">

                    <div>
                        <span>Monthly Revenue</span>
                        <strong class="revenue-text">
                            {{ $currencySymbol }}
                            {{ number_format($salesRevenueThisMonth, 2) }}
                        </strong>
                    </div>

                    <div>
                        <span>Monthly Expenses</span>
                        <strong class="expense-text">
                            {{ $currencySymbol }}
                            {{ number_format($totalExpensesThisMonth, 2) }}
                        </strong>
                    </div>

                    <div>
                        <span>Net Profit</span>
                        <strong class="{{ $netProfitThisMonth >= 0 ? 'revenue-text' : 'negative-text' }}">
                            {{ $currencySymbol }}
                            {{ number_format($netProfitThisMonth, 2) }}
                        </strong>
                    </div>

                </div>

            </div>

        </section>


        {{-- =========================================
             CURRENT FLOCK COMPOSITION
        ========================================= --}}

        <section class="farm-section">

            <div class="farm-section-heading">
                <div>
                    <h2>Current Flock Composition</h2>
                    <p>Live parent bird and chick stock</p>
                </div>
            </div>

            <div class="farm-stock-grid">

                <article class="farm-stock-card">
                    <div class="farm-stock-icon">🐔</div>
                    <div>
                        <span>Hens</span>
                        <strong>{{ number_format($henStock) }}</strong>
                    </div>
                    <small>{{ number_format($henPercent, 1) }}%</small>
                </article>

                <article class="farm-stock-card">
                    <div class="farm-stock-icon">🐓</div>
                    <div>
                        <span>Roosters</span>
                        <strong>{{ number_format($roosterStock) }}</strong>
                    </div>
                    <small>{{ number_format($roosterPercent, 1) }}%</small>
                </article>

                <article class="farm-stock-card">
                    <div class="farm-stock-icon">🐣</div>
                    <div>
                        <span>Live Chicks</span>
                        <strong>{{ number_format($liveChicks) }}</strong>
                    </div>
                    <small>{{ number_format($chickPercent, 1) }}%</small>
                </article>

                <article class="farm-stock-card farm-stock-total">
                    <div class="farm-stock-icon">🌿</div>
                    <div>
                        <span>Total Livestock</span>
                        <strong>{{ number_format($flockTotal) }}</strong>
                    </div>
                    <small>Current stock</small>
                </article>

            </div>

        </section>


        {{-- =========================================
             EMPTY STATE
        ========================================= --}}

        @if ($totalParentBirds === 0)

            <div class="farm-empty-state">

                <span>🐔</span>

                <div>
                    <strong>No Active Parent Flocks</strong>
                    <p>
                        Add your first parent flock to start tracking
                        livestock and farm production.
                    </p>
                </div>

                <a href="{{ \App\Filament\Resources\ParentFlocks\ParentFlockResource::getUrl('create') }}">
                    Add Parent Flock
                </a>

            </div>

        @endif

    </div>


    {{-- =============================================
         COMPLETE DASHBOARD CSS
    ============================================= --}}

    <style>

        .farm-overview {
            --farm-green: #15803d;
            --farm-green-dark: #166534;
            --farm-border: #e5e7eb;
            --farm-muted: #64748b;
        }

        /* =========================================
           LIGHT GREEN HEADER
        ========================================= */

        .farm-header {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            padding: 30px;
            border: 1px solid #c6e5ce;
            border-radius: 20px;
            background:
                radial-gradient(ellipse at 95% 5%, #b8e6c455 0%, transparent 40%),
                linear-gradient(125deg, #f0faf1 0%, #e6f5e9 55%, #f5fbf4 100%);
            box-shadow: 0 10px 28px rgba(22, 101, 52, .07);
        }

        .farm-header::before {
            position: absolute;
            z-index: -1;
            top: -110px;
            right: -80px;
            width: 300px;
            height: 300px;
            border: 1px solid #399b5b18;
            border-radius: 50%;
            content: "";
        }

        .farm-header::after {
            position: absolute;
            z-index: -1;
            right: 20px;
            bottom: -180px;
            width: 330px;
            height: 330px;
            border: 1px solid #399b5b14;
            border-radius: 50%;
            content: "";
        }

        .farm-header-top {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 25px;
        }

        .farm-header-content {
            min-width: 0;
            max-width: 750px;
        }

        .farm-header-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #39834f;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .farm-header-eyebrow-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #16a34a;
            box-shadow: 0 0 0 4px #16a34a20;
        }

        .farm-header-content h1 {
            margin-top: 15px;
            color: #174b2b !important;
            font-size: clamp(27px, 4vw, 39px);
            font-weight: 850;
            letter-spacing: -1.4px;
            line-height: 1.15;
        }

        .farm-header-description {
            max-width: 600px;
            margin-top: 11px;
            color: #52745b !important;
            font-size: 13px;
            line-height: 1.8;
        }

        .farm-header-status {
            display: inline-flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 9px;
            margin-top: 20px;
            color: #356b43 !important;
            font-size: 11px;
            font-weight: 650;
        }

        .farm-status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #16a34a;
            box-shadow: 0 0 0 4px #16a34a20;
        }

        .farm-status-divider {
            width: 1px;
            height: 13px;
            margin: 0 3px;
            background: #8dbb96;
        }

        .farm-header-report {
            display: flex;
            min-width: 175px;
            flex-direction: column;
            gap: 7px;
            padding: 17px;
            border: 1px solid #c9e4ce;
            border-radius: 13px;
            background: rgba(255, 255, 255, .72);
            box-shadow: 0 4px 12px #16653408;
        }

        .farm-report-label {
            color: #52835c;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1.2px;
        }

        .farm-header-report strong {
            color: #1d6034 !important;
            font-size: 20px;
            font-weight: 800;
        }

        .farm-report-date {
            color: #66866d;
            font-size: 10px;
        }

        .farm-header-divider {
            position: relative;
            z-index: 2;
            height: 1px;
            margin: 25px 0 15px;
            background: #c9e3ce;
        }


        /* =========================================
           ANIMATED CHICKEN
        ========================================= */

        .farm-chicken-track {
            position: relative;
            z-index: 2;
            display: block !important;
            width: 100%;
            height: 75px;
            margin: 0 0 15px;
            overflow: hidden;
        }

        .farm-chicken-walker {
            position: absolute;
            top: 3px;
            left: 0;
            display: flex;
            width: 60px;
            height: 57px;
            align-items: center;
            justify-content: center;
            animation: farm-chicken-move 7s ease-in-out infinite alternate !important;
            will-change: transform;
        }

        .farm-chicken-body {
            display: block !important;
            font-family: "Apple Color Emoji", "Segoe UI Emoji", sans-serif;
            font-size: 45px !important;
            line-height: 1;
            transform-origin: center bottom;
            animation: farm-chicken-bounce .35s ease-in-out infinite alternate !important;
            filter: drop-shadow(0 4px 5px #00000025);
        }

        .farm-chicken-shadow {
            position: absolute;
            bottom: 0;
            left: 12px;
            width: 37px;
            height: 6px;
            border-radius: 50%;
            background: #386b4535;
            filter: blur(4px);
            animation: farm-chicken-shadow-animation .35s ease-in-out infinite alternate;
        }

        @keyframes farm-chicken-move {
            from {
                transform: translateX(0);
            }
            to {
                transform: translateX(calc(100cqw - 60px));
            }
        }

        @keyframes farm-chicken-bounce {
            from {
                transform: translateY(0) rotate(-5deg);
            }
            to {
                transform: translateY(-7px) rotate(5deg);
            }
        }

        @keyframes farm-chicken-shadow-animation {
            from {
                transform: scaleX(1);
                opacity: .7;
            }
            to {
                transform: scaleX(.65);
                opacity: .3;
            }
        }


        /* =========================================
           HEADER INFORMATION ICONS
        ========================================= */

        .farm-header-information {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
        }

        .farm-information-item {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 11px;
        }

        .farm-header-information .farm-information-icon {
            display: inline-flex !important;
            width: 42px !important;
            height: 42px !important;
            min-width: 42px !important;
            flex: 0 0 42px !important;
            align-items: center !important;
            justify-content: center !important;
            border: 1px solid rgba(255, 255, 255, .55) !important;
            border-radius: 12px !important;
            background: #27834d !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            font-size: 19px !important;
            font-weight: 800 !important;
            line-height: 1 !important;
            opacity: 1 !important;
            box-shadow: 0 3px 10px rgba(22, 101, 52, .12);
        }

        .farm-header-information .farm-information-icon svg {
            display: block !important;
            width: 21px !important;
            height: 21px !important;
            stroke: #ffffff !important;
            fill: none !important;
            stroke-width: 2 !important;
            stroke-linecap: round;
            stroke-linejoin: round;
            opacity: 1 !important;
        }

        .farm-information-item div {
            min-width: 0;
        }

        .farm-information-item small {
            display: block;
            margin-bottom: 5px;
            color: #54805e !important;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .farm-information-item strong {
            display: block;
            overflow: hidden;
            color: #214e30 !important;
            font-size: 11px;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }


        /* =========================================
           SECTION HEADINGS
        ========================================= */

        .farm-section {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .farm-section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .farm-section-heading h2 {
            color: #111827;
            font-size: 18px;
            font-weight: 750;
            letter-spacing: -.4px;
        }

        .dark .farm-section-heading h2 {
            color: #f9fafb;
        }

        .farm-section-heading p {
            margin-top: 4px;
            color: #94a3b8;
            font-size: 12px;
        }

        .farm-section-badge {
            padding: 6px 10px;
            border-radius: 7px;
            background: #ecfdf5;
            color: #047857;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .6px;
        }

        .dark .farm-section-badge {
            background: #064e3b55;
            color: #6ee7b7;
        }


        /* =========================================
           QUICK ACTIONS - PREMIUM DESIGN
        ========================================= */

        .farm-quick-section {
            display: flex;
            flex-direction: column;
        }

        .farm-quick-section .farm-section-heading {
            margin-bottom: 0 !important;
        }

        .farm-quick-section .farm-section-heading h2 {
            font-size: 20px;
            font-weight: 800;
        }

        .farm-quick-section .farm-section-heading p {
            margin-top: 7px;
            line-height: 1.6;
        }

        .farm-actions {
            display: grid !important;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            align-items: stretch;
            gap: 13px;
            margin-top: 27px !important;
            padding-top: 0 !important;
        }

        .farm-actions .farm-action {
            position: relative;
            display: flex !important;
            min-width: 0;
            min-height: 68px;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 11px;
            padding: 12px 13px !important;
            overflow: hidden;
            border: 1px solid #e2eae3;
            border-radius: 13px;
            background: #ffffff;
            color: #334b39;
            text-decoration: none !important;
            box-shadow: 0 3px 10px rgba(25, 70, 35, .045);
            transition: all .22s ease;
        }

        .farm-actions .farm-action::before {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 3px;
            border-radius: 3px 0 0 3px;
            background: var(--action-color, #16834b);
            content: "";
            opacity: .85;
        }

        .farm-actions .farm-action:hover {
            border-color: var(--action-color, #16834b);
            background: var(--action-soft, #f1faf3);
            box-shadow: 0 9px 20px rgba(25, 70, 35, .09);
            transform: translateY(-3px);
        }

        .farm-action-icon {
            display: inline-flex !important;
            width: 39px !important;
            height: 39px !important;
            min-width: 39px !important;
            flex: 0 0 39px !important;
            align-items: center !important;
            justify-content: center !important;
            margin: 0 !important;
            border: 0;
            border-radius: 11px;
            background: var(--action-soft, #eaf6ed);
            color: var(--action-color, #16834b);
            line-height: 1 !important;
        }

        .farm-action-icon svg {
            display: block;
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.9;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .farm-action-label {
            position: static !important;
            display: block !important;
            flex: 1;
            min-width: 0;
            margin: 0 !important;
            color: #34483a;
            font-size: 11px;
            font-weight: 750;
            line-height: 1.35 !important;
            text-align: left;
            white-space: normal;
        }

        .farm-action-arrow {
            display: inline-flex;
            width: 23px;
            height: 23px;
            min-width: 23px;
            align-items: center;
            justify-content: center;
            margin-left: auto;
            border-radius: 7px;
            background: #f1f5f1;
            color: #7b8c7e;
            font-size: 15px;
            line-height: 1;
            transition: all .2s ease;
        }

        .farm-action:hover .farm-action-arrow {
            background: var(--action-color, #16834b);
            color: #ffffff;
        }

        .farm-actions .farm-action:nth-child(1) {
            --action-color: #15803d;
            --action-soft: #eaf7ed;
        }

        .farm-actions .farm-action:nth-child(2) {
            --action-color: #2563eb;
            --action-soft: #eff5ff;
        }

        .farm-actions .farm-action:nth-child(3) {
            --action-color: #d18a16;
            --action-soft: #fff8e9;
        }

        .farm-actions .farm-action:nth-child(4) {
            --action-color: #7c3aed;
            --action-soft: #f4efff;
        }

        .farm-actions .farm-action:nth-child(5) {
            --action-color: #db6078;
            --action-soft: #fff0f3;
        }

        .farm-actions .farm-action-primary {
            border-color: #b9ddc1;
            background: #eff9f0;
        }

        .farm-actions .farm-action-primary .farm-action-label {
            color: #166534;
        }

        .dark .farm-actions .farm-action {
            border-color: #ffffff16;
            background: #17231e;
        }

        .dark .farm-action-label {
            color: #e2e8e2;
        }


        /* =========================================
           PRODUCTION CARDS
        ========================================= */

        .farm-stat-card {
            min-width: 0;
            padding: 17px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 2px 9px #00000004;
            transition: .2s ease;
        }

        .dark .farm-stat-card {
            border-color: #ffffff12;
            background: #111c19;
        }

        .farm-stat-card:hover {
            border-color: #a7f3d0;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px #00000008;
        }

        .farm-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 7px;
        }

        .farm-card-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 650;
            line-height: 1.5;
        }

        .farm-card-icon {
            font-size: 20px;
        }

        .farm-stat-value {
            display: block;
            margin-top: 15px;
            color: #111827;
            font-size: clamp(21px, 2.3vw, 27px);
            font-weight: 800;
            letter-spacing: -.8px;
            overflow-wrap: anywhere;
        }

        .dark .farm-stat-value {
            color: #ffffff;
        }

        .farm-stat-hint {
            min-height: 31px;
            margin-top: 7px;
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.6;
        }

        .farm-card-bottom {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid #f1f5f9;
            color: #94a3b8;
            font-size: 9px;
            font-weight: 600;
        }

        .dark .farm-card-bottom {
            border-color: #ffffff0c;
        }

        .farm-card-bottom span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #10b981;
        }


        /* =========================================
           FINANCIAL CARDS
        ========================================= */

        .farm-finance-card {
            min-width: 0;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 15px;
            background: #ffffff;
            transition: .2s ease;
        }

        .dark .farm-finance-card {
            border-color: #ffffff12;
            background: #111c19;
        }

        .farm-finance-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 18px #00000008;
        }

        .farm-finance-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .farm-finance-top p {
            color: #64748b;
            font-size: 12px;
            font-weight: 650;
        }

        .farm-finance-top strong {
            display: block;
            margin-top: 11px;
            color: #111827;
            font-size: clamp(19px, 2.2vw, 25px);
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .dark .farm-finance-top strong {
            color: #ffffff;
        }

        .farm-finance-card.green .farm-finance-top strong {
            color: #059669;
        }

        .farm-finance-card.amber .farm-finance-top strong {
            color: #d97706;
        }

        .farm-finance-card.red .farm-finance-top strong {
            color: #dc2626;
        }

        .farm-finance-top > span {
            font-size: 23px;
        }

        .farm-finance-hint {
            margin-top: 15px;
            padding-top: 11px;
            border-top: 1px solid #f1f5f9;
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.7;
        }

        .dark .farm-finance-hint {
            border-color: #ffffff10;
        }


        /* =========================================
           LIVE STATUS
        ========================================= */

        .farm-live-status {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 8px 11px;
            border: 1px solid #a7f3d0;
            border-radius: 20px;
            background: #ecfdf5;
            color: #047857;
            font-size: 10px;
            font-weight: 700;
        }

        .farm-live-status span {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 4px #10b98120;
        }

        .dark .farm-live-status {
            border-color: #065f46;
            background: #064e3b40;
            color: #6ee7b7;
        }


        /* =========================================
           CHART CARD
        ========================================= */

        .farm-chart-card {
            min-width: 0;
            padding: 22px;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 3px 14px #00000004;
        }

        .dark .farm-chart-card {
            border-color: #ffffff12;
            background: #111c19;
        }

        .farm-chart-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .farm-chart-eyebrow {
            color: #059669;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
        }

        .farm-chart-heading h3 {
            margin-top: 6px;
            color: #111827;
            font-size: 18px;
            font-weight: 750;
        }

        .dark .farm-chart-heading h3 {
            color: #ffffff;
        }

        .farm-chart-heading p {
            margin-top: 5px;
            color: #94a3b8;
            font-size: 11px;
        }

        .farm-chart-total {
            padding: 11px 14px;
            border: 1px solid #d1fae5;
            border-radius: 11px;
            background: #f0fdf4;
        }

        .dark .farm-chart-total {
            border-color: #065f4644;
            background: #064e3b30;
        }

        .farm-chart-total span {
            display: block;
            color: #64748b;
            font-size: 8px;
            font-weight: 750;
            letter-spacing: .7px;
        }

        .farm-chart-total strong {
            display: block;
            margin-top: 5px;
            color: #047857;
            font-size: 16px;
            font-weight: 800;
        }

        .dark .farm-chart-total strong {
            color: #6ee7b7;
        }

        .farm-chart-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 22px;
            margin-bottom: 14px;
        }

        .farm-chart-legend span {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #64748b;
            font-size: 10px;
            font-weight: 650;
        }

        .farm-legend-dot {
            width: 9px;
            height: 9px;
            border-radius: 3px;
        }

        .revenue-dot {
            background: #10b981;
        }

        .expense-dot {
            background: #f59e0b;
        }

        .farm-chart-scene {
            position: relative;
            height: 315px;
            overflow: hidden;
            border: 1px solid #edf2ef;
            border-radius: 13px;
            background: linear-gradient(180deg, #fbfefc, #f7faf8);
        }

        .dark .farm-chart-scene {
            border-color: #ffffff0c;
            background: linear-gradient(180deg, #17251e, #101a15);
        }

        .farm-chart-grid {
            position: absolute;
            inset: 15px 12px 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            pointer-events: none;
        }

        .farm-grid-line {
            width: 100%;
            height: 1px;
            border-top: 1px dashed #94a3b82c;
        }

        .farm-chart-columns {
            position: absolute;
            inset: 13px 15px 0;
            display: flex;
            align-items: stretch;
            justify-content: space-around;
            gap: 8px;
        }

        .farm-chart-column {
            position: relative;
            display: flex;
            flex: 1;
            min-width: 0;
            height: 100%;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
        }

        .farm-chart-values {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            font-size: 9px;
            font-weight: 700;
            line-height: 1.2;
            white-space: nowrap;
        }

        .revenue-value {
            color: #059669;
        }

        .expense-value {
            color: #d97706;
        }

        .dark .revenue-value {
            color: #6ee7b7;
        }

        .dark .expense-value {
            color: #fcd34d;
        }

        .farm-bars-wrapper {
            position: absolute;
            top: 46px;
            right: 0;
            bottom: 47px;
            left: 0;
            display: flex;
            align-items: stretch;
            justify-content: center;
            gap: 5px;
        }

        .farm-chart-track {
            display: flex;
            width: 34%;
            max-width: 35px;
            height: 100%;
            align-items: flex-end;
            justify-content: center;
        }

        .farm-chart-bar {
            position: relative;
            width: 100%;
            height: var(--bar-height);
            min-height: 0;
            border-radius: 5px 5px 2px 2px;
            transform: perspective(500px) rotateX(-3deg);
            transform-origin: bottom;
            animation: farm-bar-rise .9s cubic-bezier(.2,.8,.2,1) both;
            transition: filter .2s ease;
        }

        .farm-chart-bar::before {
            position: absolute;
            top: -5px;
            left: 3px;
            width: calc(100% - 3px);
            height: 5px;
            border-radius: 3px 3px 0 0;
            content: "";
            transform: skewX(-35deg);
            transform-origin: bottom left;
            filter: brightness(1.12);
        }

        .farm-chart-bar::after {
            position: absolute;
            top: 3px;
            right: -5px;
            width: 5px;
            height: calc(100% - 3px);
            border-radius: 0 2px 2px 0;
            content: "";
            transform: skewY(-35deg);
            transform-origin: left top;
            filter: brightness(.78);
        }

        .revenue-bar {
            background: linear-gradient(90deg, #059669, #34d399);
            box-shadow: 2px 3px 7px #05966920;
        }

        .revenue-bar::before,
        .revenue-bar::after {
            background: #047857;
        }

        .expense-bar {
            background: linear-gradient(90deg, #d97706, #fbbf24);
            box-shadow: 2px 3px 7px #d9770620;
        }

        .expense-bar::before,
        .expense-bar::after {
            background: #b45309;
        }

        .farm-chart-column:hover .farm-chart-bar {
            filter: brightness(1.1);
        }

        .farm-chart-month {
            position: absolute;
            bottom: 24px;
            color: #475569;
            font-size: 11px;
            font-weight: 750;
        }

        .farm-chart-year {
            position: absolute;
            bottom: 8px;
            color: #94a3b8;
            font-size: 8px;
        }

        .dark .farm-chart-month {
            color: #e2e8f0;
        }

        .farm-chart-footer {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 18px;
            padding-top: 17px;
            border-top: 1px solid #f1f5f9;
        }

        .dark .farm-chart-footer {
            border-color: #ffffff12;
        }

        .farm-chart-footer div {
            min-width: 0;
        }

        .farm-chart-footer span {
            display: block;
            color: #94a3b8;
            font-size: 10px;
        }

        .farm-chart-footer strong {
            display: block;
            margin-top: 6px;
            font-size: clamp(12px, 1.5vw, 16px);
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .revenue-text {
            color: #059669;
        }

        .expense-text {
            color: #d97706;
        }

        .negative-text {
            color: #dc2626;
        }


        /* =========================================
           FLOCK COMPOSITION
        ========================================= */

        .farm-stock-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .farm-stock-card {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 11px;
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 13px;
            background: #ffffff;
        }

        .dark .farm-stock-card {
            border-color: #ffffff12;
            background: #111c19;
        }

        .farm-stock-icon {
            display: flex;
            width: 39px;
            height: 39px;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: #ecfdf5;
            font-size: 19px;
        }

        .farm-stock-card div:nth-child(2) {
            flex: 1;
            min-width: 0;
        }

        .farm-stock-card span {
            display: block;
            color: #64748b;
            font-size: 10px;
        }

        .farm-stock-card strong {
            display: block;
            margin-top: 3px;
            color: #111827;
            font-size: 20px;
            font-weight: 800;
        }

        .dark .farm-stock-card strong {
            color: #ffffff;
        }

        .farm-stock-card small {
            color: #059669;
            font-size: 10px;
            font-weight: 750;
        }

        .farm-stock-total {
            border-color: #a7f3d0;
            background: #f0fdf4;
        }

        .dark .farm-stock-total {
            border-color: #065f4644;
            background: #064e3b30;
        }


        /* =========================================
           EMPTY STATE
        ========================================= */

        .farm-empty-state {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            padding: 19px;
            border: 1px dashed #a7f3d0;
            border-radius: 14px;
            background: #f0fdf4;
        }

        .dark .farm-empty-state {
            border-color: #065f46;
            background: #064e3b20;
        }

        .farm-empty-state > span {
            font-size: 29px;
        }

        .farm-empty-state div {
            flex: 1;
            min-width: 180px;
        }

        .farm-empty-state strong {
            color: #047857;
            font-size: 13px;
        }

        .farm-empty-state p {
            margin-top: 4px;
            color: #64748b;
            font-size: 11px;
        }

        .farm-empty-state a {
            padding: 9px 13px;
            border-radius: 8px;
            background: #047857;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
        }


        /* =========================================
           CHART ANIMATION
        ========================================= */

        @keyframes farm-bar-rise {
            from {
                height: 0;
                opacity: .3;
            }
            to {
                height: var(--bar-height);
                opacity: 1;
            }
        }


        /* =========================================
           RESPONSIVE DESIGN
        ========================================= */

        @media (max-width: 1200px) {

            .farm-actions {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .farm-header-information {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 767px) {

            .farm-header {
                padding: 21px;
            }

            .farm-header-top {
                flex-direction: column;
            }

            .farm-header-report {
                width: 100%;
            }

            .farm-header-information {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 17px 12px;
            }

            .farm-stock-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .farm-chicken-track {
                height: 65px;
            }

            .farm-actions {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                margin-top: 23px !important;
            }

        }

        @media (max-width: 640px) {

            .farm-header {
                padding: 17px;
                border-radius: 15px;
            }

            .farm-header-content h1 {
                font-size: 27px;
            }

            .farm-header-description {
                font-size: 11px;
            }

            .farm-header-status {
                font-size: 10px;
            }

            .farm-header-information {
                gap: 15px 9px;
            }

            .farm-header-information .farm-information-icon {
                width: 35px !important;
                height: 35px !important;
                min-width: 35px !important;
                flex-basis: 35px !important;
                font-size: 15px !important;
            }

            .farm-header-information .farm-information-icon svg {
                width: 18px !important;
                height: 18px !important;
            }

            .farm-information-item {
                gap: 8px;
            }

            .farm-information-item small {
                font-size: 7px;
            }

            .farm-information-item strong {
                font-size: 10px;
            }

            .farm-chicken-track {
                height: 65px;
            }

            .farm-actions {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-top: 23px !important;
            }

            .farm-actions .farm-action {
                min-height: 65px;
                gap: 8px;
                padding: 10px 9px !important;
            }

            .farm-action-icon {
                width: 34px !important;
                height: 34px !important;
                min-width: 34px !important;
                flex-basis: 34px !important;
            }

            .farm-action-icon svg {
                width: 18px;
                height: 18px;
            }

            .farm-action-label {
                font-size: 10px;
            }

            .farm-action-arrow {
                width: 19px;
                height: 19px;
                min-width: 19px;
                font-size: 12px;
            }

            .farm-stat-card {
                padding: 13px;
            }

            .farm-finance-card {
                padding: 15px;
            }

            .farm-chart-card {
                padding: 15px;
            }

            .farm-chart-scene {
                height: 275px;
            }

            .farm-chart-columns {
                inset-inline: 7px;
                gap: 3px;
            }

            .farm-chart-values {
                font-size: 8px;
            }

            .farm-bars-wrapper {
                gap: 3px;
            }

            .farm-chart-footer {
                gap: 7px;
            }

            .farm-chart-footer span {
                font-size: 9px;
            }

            .farm-stock-card {
                padding: 12px 9px;
                gap: 7px;
            }

            .farm-stock-icon {
                width: 31px;
                height: 31px;
                font-size: 15px;
            }

            .farm-stock-card strong {
                font-size: 17px;
            }

        }

        @media (max-width: 380px) {

            .farm-header-information {
                grid-template-columns: 1fr;
            }

            .farm-chart-values {
                font-size: 7px;
            }

            .farm-chart-columns {
                inset-inline: 4px;
                gap: 1px;
            }

            .farm-chart-footer {
                grid-template-columns: 1fr;
            }

            .farm-actions .farm-action {
                gap: 6px;
                padding-inline: 7px !important;
            }

            .farm-action-arrow {
                display: none;
            }

        }


        /* =========================================
           ACCESSIBILITY
        ========================================= */

        @media (prefers-reduced-motion: reduce) {

            .farm-chart-bar,
            .farm-chicken-walker,
            .farm-chicken-body,
            .farm-chicken-shadow {
                animation: none !important;
            }

            .farm-action,
            .farm-stat-card,
            .farm-finance-card {
                transition: none !important;
            }

        }

    </style>

</x-filament-widgets::widget>

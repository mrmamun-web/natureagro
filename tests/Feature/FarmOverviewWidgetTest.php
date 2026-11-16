<?php

namespace Tests\Feature;

use App\Filament\Widgets\FarmOverviewWidget;
use App\Models\ChickBatch;
use App\Models\FarmTransaction;
use App\Models\FlockDailyRecord;
use App\Models\ParentFlock;
use App\Models\Sale;
use App\Models\SupplyProduct;
use App\Models\SupplyUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FarmOverviewWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel('admin');
    }

    public function test_dashboard_displays_live_active_stock_and_recent_records(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Layer parent flock',
            'initial_male_quantity' => 3,
            'initial_female_quantity' => 13,
        ]);

        FlockDailyRecord::query()->create([
            'parent_flock_id' => $flock->id,
            'recorded_on' => today(),
            'male_mortality' => 0,
            'female_mortality' => 0,
            'eggs_collected' => 22,
            'good_eggs' => 20,
            'damaged_eggs' => 2,
            'feed_consumed_kg' => 4,
            'health_status' => ParentFlock::HEALTHY,
        ]);

        ChickBatch::query()->create([
            'name' => 'November hatch',
            'source' => ChickBatch::SOURCE_PURCHASED,
            'hatch_date' => today()->subDays(10),
            'initial_quantity' => 15,
        ]);

        Livewire::test(FarmOverviewWidget::class)
            ->assertSee('Parent birds')
            ->assertSee('16')
            ->assertSee('Eggs collected today')
            ->assertSee('22')
            ->assertSee('Live chicks')
            ->assertSee('15')
            ->assertDontSee('/storage/farm-logos/Nature Agro Green Horizon Logo.png', false);
    }

    public function test_dashboard_shows_an_empty_state_without_poultry_records(): void
    {
        Livewire::test(FarmOverviewWidget::class)
            ->assertSee('No active stock yet')
            ->assertSee('Your poultry list is empty');
    }

    public function test_dashboard_displays_recorded_sales_and_customer_dues(): void
    {
        Sale::query()->create([
            'product_type' => Sale::PRODUCT_EGG,
            'quantity' => 3,
            'unit_price' => 10,
            'total_amount' => 30,
            'paid_amount' => 10,
            'due_amount' => 20,
            'payment_status' => Sale::PAYMENT_PARTIAL,
            'sold_on' => today(),
        ]);

        Livewire::test(FarmOverviewWidget::class)
            ->assertSee('Eggs sold today')
            ->assertSee('3')
            ->assertSee('Sales revenue this month')
            ->assertSee('৳ 30.00')
            ->assertSee('Customer dues')
            ->assertSee('৳ 20.00')
            ->assertSee('View sales');
    }

    public function test_dashboard_displays_recorded_other_expenses(): void
    {
        FarmTransaction::query()->create([
            'transaction_type' => FarmTransaction::TYPE_EXPENSE,
            'category' => FarmTransaction::EXPENSE_ELECTRICITY,
            'amount' => 1250,
            'transaction_on' => today(),
            'payment_method' => 'cash',
        ]);

        Livewire::test(FarmOverviewWidget::class)
            ->assertSee('Other expenses this month')
            ->assertSee('৳ 1,250.00');
    }

    public function test_dashboard_displays_feed_costs_medicine_costs_and_health_alerts(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Healthcare dashboard flock',
            'initial_male_quantity' => 2,
            'initial_female_quantity' => 10,
        ]);
        $feed = SupplyProduct::query()->create([
            'name' => 'Layer feed',
            'type' => SupplyProduct::TYPE_FEED,
            'unit' => 'kg',
            'minimum_stock' => 5,
        ]);
        $medicine = SupplyProduct::query()->create([
            'name' => 'Vaccine',
            'type' => SupplyProduct::TYPE_MEDICINE,
            'unit' => 'vial',
            'minimum_stock' => 2,
        ]);

        SupplyUsage::query()->create([
            'supply_product_id' => $feed->id,
            'parent_flock_id' => $flock->id,
            'used_on' => today(),
            'quantity' => 3,
            'total_cost' => 80,
            'usage_type' => 'feed_consumption',
        ]);
        SupplyUsage::query()->create([
            'supply_product_id' => $medicine->id,
            'parent_flock_id' => $flock->id,
            'used_on' => today(),
            'quantity' => 1,
            'total_cost' => 15,
            'usage_type' => 'vaccination',
            'dosage' => '1 dose',
            'next_dose_on' => today()->addDays(3),
        ]);

        Livewire::test(FarmOverviewWidget::class)
            ->assertSee('Feed consumed this month')
            ->assertSee('৳ 80.00')
            ->assertSee('Medicine used this month')
            ->assertSee('৳ 15.00')
            ->assertSee('Upcoming vaccinations')
            ->assertSee('Layer feed')
            ->assertSee('Vaccine');
    }
}

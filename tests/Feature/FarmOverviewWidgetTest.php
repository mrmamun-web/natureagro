<?php

namespace Tests\Feature;

use App\Filament\Pages\FarmReports;
use App\Filament\Pages\FarmSettings;
use App\Filament\Resources\ChickBatches\ChickBatchResource;
use App\Filament\Resources\EggStocks\EggStockResource;
use App\Filament\Resources\FarmTransactions\FarmTransactionResource;
use App\Filament\Resources\Poultries\PoultryResource;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Filament\Resources\SupplyUsages\SupplyUsageResource;
use App\Filament\Widgets\FarmOverviewWidget;
use App\Models\ChickBatch;
use App\Models\FarmTransaction;
use App\Models\FlockDailyRecord;
use App\Models\ParentFlock;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\SupplyProduct;
use App\Models\SupplyPurchase;
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

    public function test_dashboard_displays_parent_stock_and_production_summary(): void
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
            'source' => ChickBatch::SOURCE_HATCHED,
            'hatch_date' => today()->subDays(10),
            'initial_quantity' => 15,
        ]);

        Livewire::test(FarmOverviewWidget::class)
            ->assertSee('Parent birds')
            ->assertSee('16')
            ->assertSee('Eggs collected today')
            ->assertSee('22')
            ->assertSee('Chicks hatched this month')
            ->assertSee('15 live')
            ->assertDontSee('/storage/farm-logos/Nature Agro Green Horizon Logo.png', false);
    }

    public function test_dashboard_shows_an_empty_state_without_poultry_records(): void
    {
        Livewire::test(FarmOverviewWidget::class)
            ->assertSee('No active parent flocks yet');
    }

    public function test_navigation_keeps_farm_report_settings_and_other_expenses_but_hides_egg_inventory(): void
    {
        $this->assertTrue(FarmReports::shouldRegisterNavigation());
        $this->assertTrue(FarmSettings::shouldRegisterNavigation());
        $this->assertTrue(FarmTransactionResource::shouldRegisterNavigation());
        $this->assertFalse(EggStockResource::shouldRegisterNavigation());
        $this->assertFalse(SupplierResource::shouldRegisterNavigation());
        $this->assertFalse(PoultryResource::shouldRegisterNavigation());
        $this->assertFalse(SupplyUsageResource::shouldRegisterNavigation());
        $this->assertSame([], SupplyUsageResource::getPages());
        $this->assertArrayNotHasKey('create', ChickBatchResource::getPages());
    }

    public function test_dashboard_displays_monthly_sales_and_net_profit(): void
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
        $batch = ChickBatch::query()->create([
            'name' => 'Current hatch batch',
            'source' => ChickBatch::SOURCE_HATCHED,
            'hatch_date' => today()->subDays(30),
            'initial_quantity' => 4,
        ]);
        Sale::query()->create([
            'product_type' => Sale::PRODUCT_CHICK,
            'chick_batch_id' => $batch->id,
            'quantity' => 4,
            'unit_price' => 20,
            'total_amount' => 80,
            'paid_amount' => 80,
            'due_amount' => 0,
            'payment_status' => Sale::PAYMENT_PAID,
            'sold_on' => today(),
        ]);
        FarmTransaction::query()->create([
            'transaction_type' => FarmTransaction::TYPE_EXPENSE,
            'category' => FarmTransaction::EXPENSE_MISCELLANEOUS,
            'amount' => 12,
            'transaction_on' => today(),
        ]);

        Livewire::test(FarmOverviewWidget::class)
            ->assertSee('3 eggs')
            ->assertSee('4 batch chickens sold')
            ->assertSee('Total sales')
            ->assertSee('৳ 110.00')
            ->assertSee('Other expenses')
            ->assertSee('৳ 12.00')
            ->assertSee('Total expenses')
            ->assertSee('৳ 12.00')
            ->assertSee('Net profit')
            ->assertSee('৳ 98.00')
            ->assertSee('Record a sale');
    }

    public function test_dashboard_displays_monthly_feed_and_medicine_purchase_totals(): void
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
        $supplier = Supplier::query()->create(['name' => 'Monthly supplier']);

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
        SupplyPurchase::query()->create([
            'supplier_id' => $supplier->id,
            'supply_product_id' => $feed->id,
            'purchased_on' => today(),
            'quantity' => 10,
            'unit_cost' => 20,
            'total_amount' => 200,
            'paid_amount' => 200,
            'due_amount' => 0,
            'payment_status' => SupplyPurchase::PAYMENT_PAID,
        ]);
        SupplyPurchase::query()->create([
            'supplier_id' => $supplier->id,
            'supply_product_id' => $medicine->id,
            'purchased_on' => today(),
            'quantity' => 2,
            'unit_cost' => 15,
            'total_amount' => 30,
            'paid_amount' => 30,
            'due_amount' => 0,
            'payment_status' => SupplyPurchase::PAYMENT_PAID,
        ]);
        SupplyPurchase::query()->create([
            'supplier_id' => $supplier->id,
            'supply_product_id' => $feed->id,
            'purchased_on' => today()->subMonth(),
            'quantity' => 20,
            'unit_cost' => 10,
            'total_amount' => 200,
            'paid_amount' => 200,
            'due_amount' => 0,
            'payment_status' => SupplyPurchase::PAYMENT_PAID,
        ]);

        Livewire::test(FarmOverviewWidget::class)
            ->assertSee('Feed purchases')
            ->assertSee('৳ 200.00')
            ->assertSee('Medicine purchases')
            ->assertSee('৳ 30.00')
            ->assertDontSee('Upcoming vaccinations');
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Pages\FarmReports;
use App\Models\ChickBatch;
use App\Models\FarmTransaction;
use App\Models\ParentFlock;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\SupplyProduct;
use App\Models\SupplyPurchase;
use App\Models\SupplyUsage;
use App\Services\FarmTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class FarmFinanceAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel('admin');
    }

    public function test_reversing_a_financial_transaction_preserves_both_entries_and_is_one_time(): void
    {
        $transaction = FarmTransaction::query()->create([
            'transaction_type' => FarmTransaction::TYPE_EXPENSE,
            'category' => FarmTransaction::EXPENSE_ELECTRICITY,
            'amount' => 1250,
            'transaction_on' => today(),
            'payment_method' => 'cash',
        ]);

        $reversal = app(FarmTransactionService::class)->reverse($transaction);

        $this->assertSame(FarmTransaction::TYPE_INCOME, $reversal->transaction_type);
        $this->assertSame((float) $transaction->amount, (float) $reversal->amount);
        $this->assertSame($transaction->id, $reversal->reversal_of_id);
        $this->assertDatabaseCount('farm_transactions', 2);

        $this->expectException(ValidationException::class);
        app(FarmTransactionService::class)->reverse($transaction);
    }

    public function test_report_page_displays_recorded_financial_activity(): void
    {
        FarmTransaction::query()->create([
            'transaction_type' => FarmTransaction::TYPE_INCOME,
            'category' => FarmTransaction::INCOME_OTHER,
            'amount' => 450,
            'transaction_on' => today(),
        ]);
        $this->assertDatabaseHas('farm_transactions', [
            'transaction_type' => FarmTransaction::TYPE_INCOME,
            'category' => FarmTransaction::INCOME_OTHER,
            'amount' => 450,
        ]);

        Livewire::test(FarmReports::class)
            ->assertSee('Other income / expenses')
            ->assertSee('৳ 450.00')
            ->assertSee('Print / Save PDF');
    }

    public function test_net_profit_subtracts_feed_medicine_and_other_expenses_from_sales(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Net profit report flock',
            'initial_male_quantity' => 1,
            'initial_female_quantity' => 5,
        ]);
        Sale::query()->create([
            'product_type' => Sale::PRODUCT_EGG,
            'quantity' => 2,
            'unit_price' => 200,
            'total_amount' => 400,
            'paid_amount' => 400,
            'due_amount' => 0,
            'payment_status' => Sale::PAYMENT_PAID,
            'sold_on' => today(),
        ]);

        $feed = SupplyProduct::query()->create([
            'name' => 'Net profit feed',
            'type' => SupplyProduct::TYPE_FEED,
            'unit' => 'kg',
        ]);
        $medicine = SupplyProduct::query()->create([
            'name' => 'Net profit medicine',
            'type' => SupplyProduct::TYPE_MEDICINE,
            'unit' => 'vial',
        ]);
        $supplier = Supplier::query()->create(['name' => 'Net profit supplier']);

        foreach ([[$feed, 50, 'feed_consumption'], [$medicine, 25, 'treatment']] as [$product, $cost, $usageType]) {
            SupplyUsage::query()->create([
                'supply_product_id' => $product->id,
                'parent_flock_id' => $flock->id,
                'used_on' => today(),
                'quantity' => 1,
                'total_cost' => $cost,
                'usage_type' => $usageType,
            ]);
        }
        foreach ([[$feed, 80], [$medicine, 20]] as [$product, $amount]) {
            SupplyPurchase::query()->create([
                'supplier_id' => $supplier->id,
                'supply_product_id' => $product->id,
                'purchased_on' => today(),
                'quantity' => 1,
                'unit_cost' => $amount,
                'total_amount' => $amount,
                'paid_amount' => $amount,
                'due_amount' => 0,
                'payment_status' => SupplyPurchase::PAYMENT_PAID,
            ]);
        }
        FarmTransaction::query()->create([
            'transaction_type' => FarmTransaction::TYPE_EXPENSE,
            'category' => FarmTransaction::EXPENSE_ELECTRICITY,
            'amount' => 25,
            'transaction_on' => today(),
        ]);

        Livewire::test(FarmReports::class)
            ->assertSee('Net profit')
            ->assertSee('৳ 275.00');
    }

    public function test_report_date_range_includes_both_selected_dates_across_months(): void
    {
        FarmTransaction::query()->create([
            'transaction_type' => FarmTransaction::TYPE_INCOME,
            'category' => FarmTransaction::INCOME_OTHER,
            'amount' => 450,
            'transaction_on' => '2026-11-14',
        ]);
        FarmTransaction::query()->create([
            'transaction_type' => FarmTransaction::TYPE_INCOME,
            'category' => FarmTransaction::INCOME_OTHER,
            'amount' => 450,
            'transaction_on' => '2026-11-15',
        ]);
        FarmTransaction::query()->create([
            'transaction_type' => FarmTransaction::TYPE_INCOME,
            'category' => FarmTransaction::INCOME_OTHER,
            'amount' => 900,
            'transaction_on' => '2027-01-15',
        ]);
        FarmTransaction::query()->create([
            'transaction_type' => FarmTransaction::TYPE_INCOME,
            'category' => FarmTransaction::INCOME_OTHER,
            'amount' => 1650,
            'transaction_on' => '2027-03-15',
        ]);
        FarmTransaction::query()->create([
            'transaction_type' => FarmTransaction::TYPE_INCOME,
            'category' => FarmTransaction::INCOME_OTHER,
            'amount' => 1000,
            'transaction_on' => '2027-03-16',
        ]);

        Livewire::test(FarmReports::class)
            ->set('requestedStartDate', '2026-11-15')
            ->set('requestedEndDate', '2027-03-15')
            ->call('applyFilters')
            ->assertSet('startDate', '2026-11-15')
            ->assertSet('endDate', '2027-03-15')
            ->assertSee('3,000.00')
            ->assertDontSee('4,450.00');
    }

    public function test_report_date_range_rejects_a_start_date_after_the_end_date(): void
    {
        Livewire::test(FarmReports::class)
            ->set('requestedStartDate', '2027-04-01')
            ->set('requestedEndDate', '2027-03-31')
            ->call('applyFilters')
            ->assertHasErrors(['requestedEndDate']);
    }

    public function test_report_can_be_downloaded_as_a_pdf_for_the_selected_date_range(): void
    {
        Livewire::test(FarmReports::class)
            ->set('requestedStartDate', '2026-11-05')
            ->set('requestedEndDate', '2026-11-20')
            ->call('downloadPdf')
            ->assertFileDownloaded('nature-agro-report-2026-11-05-to-2026-11-20.pdf');
    }

    public function test_report_includes_chicks_recorded_alive_at_the_seven_day_check(): void
    {
        ChickBatch::query()->create([
            'name' => 'Seven day survival report batch',
            'source' => ChickBatch::SOURCE_HATCHED,
            'hatch_date' => today()->subDays(7),
            'initial_quantity' => 10,
            'survived_after_7_days' => 8,
            'survival_recorded_on' => today(),
        ]);

        Livewire::test(FarmReports::class)
            ->assertSee('Chicks surviving 7-day check')
            ->assertSee('Recorded seven-day survival checks in this date range');
    }

    public function test_report_expenses_feed_and_medicine_in_the_purchase_date_range(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Purchase date report flock',
            'initial_male_quantity' => 1,
            'initial_female_quantity' => 5,
        ]);
        $feed = SupplyProduct::query()->create([
            'name' => 'Purchase date feed',
            'type' => SupplyProduct::TYPE_FEED,
            'unit' => 'kg',
        ]);
        $medicine = SupplyProduct::query()->create([
            'name' => 'Purchase date medicine',
            'type' => SupplyProduct::TYPE_MEDICINE,
            'unit' => 'vial',
        ]);
        $supplier = Supplier::query()->create(['name' => 'Purchase date supplier']);
        foreach ([[$feed, 100, '2026-03-01'], [$medicine, 50, '2026-03-31']] as [$product, $amount, $purchasedOn]) {
            SupplyPurchase::query()->create([
                'supplier_id' => $supplier->id,
                'supply_product_id' => $product->id,
                'purchased_on' => $purchasedOn,
                'quantity' => 1,
                'unit_cost' => $amount,
                'total_amount' => $amount,
                'paid_amount' => $amount,
                'due_amount' => 0,
                'payment_status' => SupplyPurchase::PAYMENT_PAID,
            ]);
        }
        SupplyUsage::query()->create([
            'supply_product_id' => $feed->id,
            'parent_flock_id' => $flock->id,
            'used_on' => '2026-03-10',
            'quantity' => 1,
            'total_cost' => 900,
            'usage_type' => 'feed_consumption',
        ]);

                Livewire::test(FarmReports::class)
            ->set('requestedStartDate', '2026-03-01')
            ->set('requestedEndDate', '2026-03-31')
            ->call('applyFilters')
            ->assertSee('Feed expense')
            ->assertSee('৳ 100.00')
            ->assertSee('Medicine expense')
            ->assertSee('৳ 50.00')
            ->assertSee('৳ -150.00');
    }
}

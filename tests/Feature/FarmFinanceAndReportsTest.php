<?php

namespace Tests\Feature;

use App\Filament\Pages\FarmReports;
use App\Models\FarmTransaction;
use App\Models\ParentFlock;
use App\Services\FarmTransactionService;
use App\Services\InventoryCostService;
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

        Livewire::test(FarmReports::class)
            ->assertSee('Other income / expenses')
            ->assertSee('৳ 450.00')
            ->assertSee('Print / Save PDF');
    }

    public function test_report_month_range_includes_every_selected_month_and_future_end_months(): void
    {
        FarmTransaction::query()->create([
            'transaction_type' => FarmTransaction::TYPE_INCOME,
            'category' => FarmTransaction::INCOME_OTHER,
            'amount' => 450,
            'transaction_on' => '2026-11-01',
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
            'transaction_on' => '2027-03-31',
        ]);
        FarmTransaction::query()->create([
            'transaction_type' => FarmTransaction::TYPE_INCOME,
            'category' => FarmTransaction::INCOME_OTHER,
            'amount' => 500,
            'transaction_on' => '2027-04-01',
        ]);

        Livewire::test(FarmReports::class)
            ->set('requestedStartMonth', '2026-11')
            ->set('requestedEndMonth', '2027-03')
            ->call('applyFilters')
            ->assertSet('startDate', '2026-11-01')
            ->assertSet('endDate', '2027-03-31')
            ->assertSee('3,000.00')
            ->assertDontSee('3,500.00');
    }

    public function test_report_month_range_rejects_a_start_month_after_the_end_month(): void
    {
        Livewire::test(FarmReports::class)
            ->set('requestedStartMonth', '2027-04')
            ->set('requestedEndMonth', '2027-03')
            ->call('applyFilters')
            ->assertHasErrors(['requestedStartMonth']);
    }

    public function test_report_displays_the_units_of_production_breeder_cost_policy(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Breeder valuation report flock',
            'initial_male_quantity' => 1,
            'initial_female_quantity' => 5,
        ]);
        app(InventoryCostService::class)->receive(
            'parent_flock',
            $flock->id,
            'female',
            5,
            500,
            today()->toDateString(),
            'purchase',
            $flock,
        );

        Livewire::test(FarmReports::class)
            ->assertSee('Breeder cost allocation')
            ->assertSee('3,000 eggs / hen');
    }
}

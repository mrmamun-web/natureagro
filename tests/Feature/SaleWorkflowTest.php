<?php

namespace Tests\Feature;

use App\Models\EggStock;
use App\Models\ParentFlock;
use App\Models\Sale;
use App\Services\InventoryCostService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SaleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_egg_sale_deducts_stock_and_tracks_partial_payments(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Deshi Flock',
            'initial_male_quantity' => 2,
            'initial_female_quantity' => 20,
        ]);

        $stock = EggStock::query()->create([
            'parent_flock_id' => $flock->id,
            'good_available' => 20,
        ]);
        app(InventoryCostService::class)->receive(
            'egg_stock',
            $stock->id,
            null,
            20,
            100,
            today()->toDateString(),
            'opening_valuation',
            $stock,
        );

        $service = app(SaleService::class);
        $sale = $service->create([
            'product_type' => Sale::PRODUCT_EGG,
            'parent_flock_id' => $flock->id,
            'quantity' => 5,
            'unit_price' => 20,
            'discount_amount' => 5,
            'paid_amount' => 40,
            'payment_method' => 'cash',
            'sold_on' => now()->toDateString(),
        ]);

        $this->assertSame('95.00', $sale->total_amount);
        $this->assertSame('55.00', $sale->due_amount);
        $this->assertSame('25.00', $sale->cost_of_goods_sold);
        $this->assertSame(Sale::PAYMENT_PARTIAL, $sale->payment_status);
        $this->assertDatabaseHas('egg_stocks', [
            'parent_flock_id' => $flock->id,
            'good_available' => 15,
        ]);

        $sale = $service->recordPayment($sale, [
            'amount' => 55,
            'paid_on' => now()->toDateString(),
            'payment_method' => 'bkash',
        ]);

        $this->assertSame('0.00', $sale->due_amount);
        $this->assertSame(Sale::PAYMENT_PAID, $sale->payment_status);
        $this->assertDatabaseCount('sale_payments', 2);
    }

    public function test_sale_cannot_consume_more_eggs_than_available(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Small Deshi Flock',
            'initial_male_quantity' => 1,
            'initial_female_quantity' => 5,
        ]);

        EggStock::query()->create([
            'parent_flock_id' => $flock->id,
            'good_available' => 3,
        ]);

        try {
            app(SaleService::class)->create([
                'product_type' => Sale::PRODUCT_EGG,
                'parent_flock_id' => $flock->id,
                'quantity' => 4,
                'unit_price' => 20,
                'discount_amount' => 0,
                'paid_amount' => 0,
                'sold_on' => now()->toDateString(),
            ]);

            $this->fail('The sale should have been rejected for insufficient stock.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.quantity', $exception->errors());
        }

        $this->assertDatabaseHas('egg_stocks', [
            'parent_flock_id' => $flock->id,
            'good_available' => 3,
        ]);
        $this->assertDatabaseCount('sales', 0);
    }
}

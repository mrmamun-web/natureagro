<?php

namespace Tests\Feature;

use App\Models\ChickBatch;
use App\Models\Customer;
use App\Models\EggStock;
use App\Models\FarmSetting;
use App\Models\ParentFlock;
use App\Models\Sale;
use App\Models\User;
use App\Services\InventoryCostService;
use App\Services\SaleService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

    public function test_hatch_batch_chicken_sale_reduces_the_selected_batch_stock(): void
    {
        $batch = ChickBatch::query()->create([
            'name' => 'March hatch',
            'source' => ChickBatch::SOURCE_HATCHED,
            'hatch_date' => today()->subMonth(),
            'initial_quantity' => 10,
        ]);
        app(InventoryCostService::class)->receive(
            'chick_batch',
            $batch->id,
            null,
            10,
            0,
            today()->toDateString(),
            'hatch',
            $batch,
        );

        $sale = app(SaleService::class)->create([
            'product_type' => Sale::PRODUCT_CHICK,
            'chick_batch_id' => $batch->id,
            'quantity' => 3,
            'unit_price' => 100,
            'paid_amount' => 0,
            'sold_on' => today()->toDateString(),
        ]);

        $this->assertSame(Sale::PRODUCT_CHICK, $sale->product_type);
        $this->assertDatabaseHas('chick_batches', [
            'id' => $batch->id,
            'current_quantity' => 7,
        ]);
    }

    public function test_sales_reject_parent_birds_and_purchased_chick_batches(): void
    {
        $purchasedBatch = ChickBatch::query()->create([
            'name' => 'Legacy purchased chicks',
            'source' => ChickBatch::SOURCE_PURCHASED,
            'hatch_date' => today()->subMonth(),
            'initial_quantity' => 10,
        ]);

        foreach ([Sale::PRODUCT_PARENT_HEN, Sale::PRODUCT_PARENT_ROOSTER, Sale::PRODUCT_POULTRY] as $productType) {
            try {
                app(SaleService::class)->create([
                    'product_type' => $productType,
                    'quantity' => 1,
                    'unit_price' => 100,
                    'paid_amount' => 0,
                    'sold_on' => today()->toDateString(),
                ]);
                $this->fail('Parent birds and poultry inventory must not be sellable products.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('data.product_type', $exception->errors());
            }
        }

        try {
            app(SaleService::class)->create([
                'product_type' => Sale::PRODUCT_CHICK,
                'chick_batch_id' => $purchasedBatch->id,
                'quantity' => 1,
                'unit_price' => 100,
                'paid_amount' => 0,
                'sold_on' => today()->toDateString(),
            ]);
            $this->fail('Only hatch-produced batches should be sellable.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.chick_batch_id', $exception->errors());
        }

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_admin_can_open_a_printable_sale_invoice_pdf(): void
    {
        filament()->setCurrentPanel('admin');
        $this->actingAs(User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]));

        FarmSetting::query()->create([
            'farm_name' => 'Green Horizon Farm',
            'owner_name' => 'Farm Owner',
            'phone_number' => '01700000000',
            'address' => 'Farm address',
            'currency' => 'BDT',
        ]);
        $customer = Customer::query()->create([
            'name' => 'Invoice Customer',
            'phone_number' => '01800000000',
            'address' => 'Customer address',
        ]);
        $sale = Sale::query()->create([
            'customer_id' => $customer->id,
            'product_type' => Sale::PRODUCT_EGG,
            'quantity' => 3,
            'unit_price' => 4.5,
            'discount_amount' => 0.5,
            'total_amount' => 13,
            'paid_amount' => 5,
            'due_amount' => 8,
            'payment_status' => Sale::PAYMENT_PARTIAL,
            'sold_on' => today()->toDateString(),
        ]);

        $response = $this->get(Filament::getPanel('admin')->route('sales.invoice', [
            'sale' => $sale,
        ]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="invoice-'.$sale->invoice_number.'.pdf"');

        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        ob_start();
        $response->baseResponse->sendContent();
        $contents = ob_get_clean();
        $this->assertStringStartsWith('%PDF-', $contents);
    }
}

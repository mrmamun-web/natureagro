<?php

namespace Tests\Feature;

use App\Models\ParentFlock;
use App\Models\Supplier;
use App\Models\SupplyProduct;
use App\Services\SupplyInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SupplyInventoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_creates_stock_and_supplier_payable_and_rejects_overpayment(): void
    {
        [$supplier, $product] = $this->createInventoryRecords();

        $service = app(SupplyInventoryService::class);

        $purchase = $service->createPurchase([
            'supplier_id' => $supplier->id,
            'supply_product_id' => $product->id,
            'purchased_on' => today()->toDateString(),
            'quantity' => 10,
            'unit_cost' => 4,
            'paid_amount' => 15,
            'payment_method' => 'cash',
        ]);

        $this->assertSame('40.00', $purchase->total_amount);
        $this->assertSame('25.00', $purchase->due_amount);

        $this->assertDatabaseHas('supply_lots', [
            'supply_purchase_id' => $purchase->id,
            'remaining_quantity' => '10.000',
        ]);

        // Overpayment must be rejected.
        try {
            $service->recordPayment($purchase, [
                'amount' => 25.01,
                'paid_on' => today()->toDateString(),
                'payment_method' => 'cash',
            ]);

            $this->fail(
                'A payment greater than the payable balance must be rejected.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'data.amount',
                $exception->errors()
            );
        }

        // Valid remaining payment.
        $purchase = $service->recordPayment($purchase, [
            'amount' => 25,
            'paid_on' => today()->toDateString(),
            'payment_method' => 'bank',
        ]);

        $this->assertSame('0.00', $purchase->due_amount);
        $this->assertSame('paid', $purchase->payment_status);

        $this->assertDatabaseCount('supply_purchase_payments', 2);
    }

    public function test_usage_consumes_oldest_lots_first_and_captures_the_lot_cost(): void
    {
        [$supplier, $product] = $this->createInventoryRecords();

        $flock = ParentFlock::query()->create([
            'name' => 'Usage Test Flock',
            'initial_male_quantity' => 2,
            'initial_female_quantity' => 12,
        ]);

        $service = app(SupplyInventoryService::class);

        // First purchase: 5 kg at 2 per kg.
        $first = $service->createPurchase(
            $this->purchaseData(
                $supplier,
                $product,
                5,
                2,
                today()->subDays(3)->toDateString()
            )
        );

        // Second purchase: 5 kg at 4 per kg.
        $second = $service->createPurchase(
            $this->purchaseData(
                $supplier,
                $product,
                5,
                4,
                today()->subDays(2)->toDateString()
            )
        );

        // Consume 7 kg using FIFO.
        $usage = $service->recordUsage([
            'supply_product_id' => $product->id,
            'source_type' => 'parent_flock',
            'parent_flock_id' => $flock->id,
            'used_on' => today()->toDateString(),
            'quantity' => 7,
        ]);

        // (5 x 2) + (2 x 4) = 18.
        $this->assertSame('18.00', $usage->total_cost);

        // Oldest lot must be completely consumed.
        $this->assertDatabaseHas('supply_lots', [
            'supply_purchase_id' => $first->id,
            'remaining_quantity' => '0.000',
        ]);

        // Second lot must have 3 kg remaining.
        $this->assertDatabaseHas('supply_lots', [
            'supply_purchase_id' => $second->id,
            'remaining_quantity' => '3.000',
        ]);

        $this->assertDatabaseCount('supply_usage_lots', 2);
        $this->assertDatabaseCount('supply_stock_movements', 4);
    }

    public function test_medicine_usage_cannot_consume_expired_lots_or_go_below_zero(): void
    {
        $supplier = Supplier::query()->create([
            'name' => 'Medicine Supplier',
        ]);

        $product = SupplyProduct::query()->create([
            'name' => 'Medicine',
            'type' => SupplyProduct::TYPE_MEDICINE,
            'unit' => 'bottle',
        ]);

        $flock = ParentFlock::query()->create([
            'name' => 'Medicine Test Flock',
            'initial_male_quantity' => 2,
            'initial_female_quantity' => 12,
        ]);

        $service = app(SupplyInventoryService::class);

        // Expired lot: 5 bottles.
        $expired = $service->createPurchase(
            array_merge(
                $this->purchaseData(
                    $supplier,
                    $product,
                    5,
                    2,
                    today()->subDays(5)->toDateString()
                ),
                [
                    'expires_on' => today()->subDay()->toDateString(),
                ]
            )
        );

        // Valid lot: only 3 bottles.
        $valid = $service->createPurchase(
            array_merge(
                $this->purchaseData(
                    $supplier,
                    $product,
                    3,
                    4,
                    today()->subDays(2)->toDateString()
                ),
                [
                    'expires_on' => today()->addDays(30)->toDateString(),
                ]
            )
        );

        // Requesting 4 bottles must fail because only 3 valid bottles exist.
        try {
            $service->recordUsage([
                'supply_product_id' => $product->id,
                'source_type' => 'parent_flock',
                'parent_flock_id' => $flock->id,
                'used_on' => today()->toDateString(),
                'quantity' => 4,
            ]);

            $this->fail(
                'Expired stock and unavailable quantity must not be consumed.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'data.quantity',
                $exception->errors()
            );
        }

        // Expired stock must remain untouched.
        $this->assertDatabaseHas('supply_lots', [
            'supply_purchase_id' => $expired->id,
            'remaining_quantity' => '5.000',
        ]);

        // Valid stock must also remain untouched after failed transaction.
        $this->assertDatabaseHas('supply_lots', [
            'supply_purchase_id' => $valid->id,
            'remaining_quantity' => '3.000',
        ]);

        $this->assertDatabaseCount('supply_usages', 0);
    }

    public function test_vaccination_usage_records_dosage_and_next_dose_reminder(): void
    {
        $supplier = Supplier::query()->create([
            'name' => 'Vaccine Supplier',
        ]);

        $product = SupplyProduct::query()->create([
            'name' => 'Newcastle vaccine',
            'type' => SupplyProduct::TYPE_MEDICINE,
            'unit' => 'vial',
            'minimum_stock' => 1,
        ]);

        $flock = ParentFlock::query()->create([
            'name' => 'Vaccination flock',
            'initial_male_quantity' => 2,
            'initial_female_quantity' => 12,
        ]);

        $service = app(SupplyInventoryService::class);

        // Create vaccine purchase with expiry date.
        $purchase = $service->createPurchase(
            array_merge(
                $this->purchaseData(
                    $supplier,
                    $product,
                    5,
                    25,
                    today()->toDateString()
                ),
                [
                    'expires_on' => today()->addMonths(3)->toDateString(),
                ]
            )
        );

        // Record vaccination usage.
        $usage = $service->recordUsage([
            'supply_product_id' => $product->id,
            'source_type' => 'parent_flock',
            'parent_flock_id' => $flock->id,
            'used_on' => today()->toDateString(),
            'quantity' => 1,
            'usage_type' => 'vaccination',
            'dosage' => '1 dose per bird',
            'administered_by' => 'Farm admin',
            'next_dose_on' => today()->addDays(30)->toDateString(),
        ]);

        $this->assertSame('vaccination', $usage->usage_type);

        $this->assertSame(
            '1 dose per bird',
            $usage->dosage
        );

        $this->assertSame(
            'Farm admin',
            $usage->administered_by
        );

        $this->assertSame(
            today()->addDays(30)->toDateString(),
            $usage->next_dose_on->toDateString()
        );

        $this->assertSame('25.00', $usage->total_cost);

        // One vial should be consumed.
        $this->assertDatabaseHas('supply_lots', [
            'supply_purchase_id' => $purchase->id,
            'remaining_quantity' => '4.000',
        ]);
    }

    /**
     * Create common supplier and supply product records.
     */
    private function createInventoryRecords(): array
    {
        $supplier = Supplier::query()->create([
            'name' => 'Feed Supplier',
        ]);

        $product = SupplyProduct::query()->create([
            'name' => 'Layer Feed',
            'type' => SupplyProduct::TYPE_FEED,
            'unit' => 'kg',
        ]);

        return [$supplier, $product];
    }

    /**
     * Prepare common purchase data.
     */
    private function purchaseData(
        Supplier $supplier,
        SupplyProduct $product,
        float $quantity,
        float $unitCost,
        string $date
    ): array {
        return [
            'supplier_id' => $supplier->id,
            'supply_product_id' => $product->id,
            'purchased_on' => $date,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'paid_amount' => 0,
        ];
    }
}

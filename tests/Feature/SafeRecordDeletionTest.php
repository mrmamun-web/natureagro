<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EggInventoryMovement;
use App\Models\EggStock;
use App\Models\FarmTransaction;
use App\Models\FlockDailyRecord;
use App\Models\IncubationBatch;
use App\Models\InventoryCostLayer;
use App\Models\InventoryCostMovement;
use App\Models\ParentFlock;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Services\SafeRecordDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SafeRecordDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_customer_cascades_sales_and_restores_egg_stock(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Cascade sale test flock',
            'initial_male_quantity' => 0,
            'initial_female_quantity' => 0,
        ]);
        $customer = Customer::query()->create(['name' => 'Cascade test customer']);
        $sale = Sale::query()->create([
            'customer_id' => $customer->id,
            'product_type' => Sale::PRODUCT_EGG,
            'parent_flock_id' => $flock->id,
            'quantity' => 2,
            'unit_price' => 1,
            'total_amount' => 2,
            'cost_of_goods_sold' => 0,
            'paid_amount' => 2,
            'due_amount' => 0,
            'payment_status' => Sale::PAYMENT_PAID,
            'payment_method' => 'cash',
            'sold_on' => today(),
        ]);
        $stock = EggStock::query()->create([
            'parent_flock_id' => $flock->id,
            'good_available' => 3,
        ]);
        SalePayment::query()->create([
            'sale_id' => $sale->id,
            'amount' => 2,
            'paid_on' => today(),
            'payment_method' => 'cash',
        ]);
        EggInventoryMovement::query()->create([
            'parent_flock_id' => $flock->id,
            'movement_type' => EggInventoryMovement::TYPE_COLLECTION,
            'good_delta' => 5,
            'source_type' => 'test_collection',
            'source_id' => 1,
        ]);
        EggInventoryMovement::query()->create([
            'parent_flock_id' => $flock->id,
            'movement_type' => 'sale',
            'good_delta' => -2,
            'source_type' => Sale::class,
            'source_id' => $sale->id,
        ]);

        app(SafeRecordDeletionService::class)->delete($customer);

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('sale_payments', ['sale_id' => $sale->id]);
        $this->assertDatabaseMissing('egg_inventory_movements', [
            'source_type' => Sale::class,
            'source_id' => $sale->id,
        ]);
        $this->assertDatabaseHas('egg_stocks', [
            'id' => $stock->id,
            'good_available' => 5,
        ]);
    }

    public function test_unlinked_transaction_is_permanently_deleted(): void
    {
        $transaction = FarmTransaction::query()->create([
            'transaction_type' => FarmTransaction::TYPE_EXPENSE,
            'category' => FarmTransaction::EXPENSE_ELECTRICITY,
            'amount' => 100,
            'transaction_on' => today(),
        ]);

        app(SafeRecordDeletionService::class)->delete($transaction);

        $this->assertDatabaseMissing('farm_transactions', ['id' => $transaction->id]);
    }

    public function test_daily_flock_record_deletes_and_reverses_unused_egg_stock_and_cost_history(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Daily delete test flock',
            'initial_male_quantity' => 1,
            'initial_female_quantity' => 2,
        ]);
        $flock->update(['current_male_quantity' => 0]);

        $record = FlockDailyRecord::query()->create([
            'parent_flock_id' => $flock->id,
            'recorded_on' => today(),
            'health_status' => ParentFlock::HEALTHY,
            'male_mortality' => 1,
            'eggs_collected' => 3,
            'good_eggs' => 2,
            'damaged_eggs' => 1,
        ]);
        $incubation = IncubationBatch::query()->create([
            'parent_flock_id' => $flock->id,
            'eggs_set' => 1,
            'incubated_on' => today(),
            'expected_hatch_on' => today()->addDays(21),
            'status' => IncubationBatch::STATUS_INCUBATING,
        ]);
        $stock = EggStock::query()->create([
            'parent_flock_id' => $flock->id,
            'good_available' => 1,
            'damaged' => 1,
            'reserved_for_hatching' => 1,
        ]);
        $sourceType = FlockDailyRecord::class;

        $eggMovement = EggInventoryMovement::query()->create([
            'parent_flock_id' => $flock->id,
            'movement_type' => EggInventoryMovement::TYPE_COLLECTION,
            'good_delta' => 2,
            'damaged_delta' => 1,
            'source_type' => $sourceType,
            'source_id' => $record->id,
        ]);
        $incubationEggMovement = EggInventoryMovement::query()->create([
            'parent_flock_id' => $flock->id,
            'movement_type' => EggInventoryMovement::TYPE_INCUBATION_RESERVE,
            'good_delta' => -1,
            'reserved_delta' => 1,
            'source_type' => IncubationBatch::class,
            'source_id' => $incubation->id,
        ]);
        $layer = InventoryCostLayer::query()->create([
            'inventory_type' => 'egg_stock',
            'inventory_id' => $stock->id,
            'layer_type' => 'egg_collection',
            'acquired_on' => today(),
            'quantity' => 2,
            'remaining_quantity' => 2,
            'total_cost' => 20,
            'remaining_cost' => 20,
            'source_type' => $sourceType,
            'source_id' => $record->id,
        ]);
        $layer->update(['remaining_quantity' => 1, 'remaining_cost' => 10]);
        $incubationLayer = InventoryCostLayer::query()->create([
            'inventory_type' => 'incubation_batch',
            'inventory_id' => $incubation->id,
            'layer_type' => 'transfer_in',
            'acquired_on' => today(),
            'quantity' => 1,
            'remaining_quantity' => 1,
            'total_cost' => 10,
            'remaining_cost' => 10,
            'source_type' => IncubationBatch::class,
            'source_id' => $incubation->id,
        ]);
        $flockLayer = InventoryCostLayer::query()->create([
            'inventory_type' => 'parent_flock',
            'inventory_id' => $flock->id,
            'gender' => 'male',
            'layer_type' => 'opening_valuation',
            'acquired_on' => today(),
            'quantity' => 1,
            'remaining_quantity' => 0,
            'total_cost' => 100,
            'remaining_cost' => 0,
        ]);
        InventoryCostMovement::query()->create([
            'inventory_cost_layer_id' => $flockLayer->id,
            'inventory_type' => 'parent_flock',
            'inventory_id' => $flock->id,
            'gender' => 'male',
            'movement_type' => 'opening_stock',
            'quantity_delta' => 1,
            'cost_delta' => 100,
            'moved_on' => today(),
        ]);
        $mortalityCostMovement = InventoryCostMovement::query()->create([
            'inventory_cost_layer_id' => $flockLayer->id,
            'inventory_type' => 'parent_flock',
            'inventory_id' => $flock->id,
            'gender' => 'male',
            'movement_type' => 'mortality',
            'quantity_delta' => -1,
            'cost_delta' => -100,
            'moved_on' => today(),
            'source_type' => $sourceType,
            'source_id' => $record->id,
        ]);
        $costMovement = InventoryCostMovement::query()->create([
            'inventory_cost_layer_id' => $layer->id,
            'inventory_type' => 'egg_stock',
            'inventory_id' => $stock->id,
            'movement_type' => 'egg_collection',
            'quantity_delta' => 2,
            'cost_delta' => 20,
            'moved_on' => today(),
            'source_type' => $sourceType,
            'source_id' => $record->id,
        ]);
        $incubationCostMovement = InventoryCostMovement::query()->create([
            'inventory_cost_layer_id' => $layer->id,
            'inventory_type' => 'egg_stock',
            'inventory_id' => $stock->id,
            'movement_type' => 'incubation_reserve',
            'quantity_delta' => -1,
            'cost_delta' => -10,
            'moved_on' => today(),
            'source_type' => IncubationBatch::class,
            'source_id' => $incubation->id,
        ]);
        InventoryCostMovement::query()->create([
            'inventory_cost_layer_id' => $incubationLayer->id,
            'inventory_type' => 'incubation_batch',
            'inventory_id' => $incubation->id,
            'movement_type' => 'transfer_in',
            'quantity_delta' => 1,
            'cost_delta' => 10,
            'moved_on' => today(),
            'source_type' => IncubationBatch::class,
            'source_id' => $incubation->id,
        ]);

        app(SafeRecordDeletionService::class)->delete($record);

        $this->assertDatabaseMissing('flock_daily_records', ['id' => $record->id]);
        $this->assertDatabaseMissing('parent_flock_movements', ['flock_daily_record_id' => $record->id]);
        $this->assertDatabaseMissing('incubation_batches', ['id' => $incubation->id]);
        $this->assertDatabaseMissing('egg_inventory_movements', ['id' => $eggMovement->id]);
        $this->assertDatabaseMissing('egg_inventory_movements', ['id' => $incubationEggMovement->id]);
        $this->assertDatabaseMissing('inventory_cost_movements', ['id' => $costMovement->id]);
        $this->assertDatabaseMissing('inventory_cost_movements', ['id' => $incubationCostMovement->id]);
        $this->assertDatabaseMissing('inventory_cost_movements', ['id' => $mortalityCostMovement->id]);
        $this->assertDatabaseMissing('inventory_cost_layers', ['id' => $layer->id]);
        $this->assertDatabaseMissing('inventory_cost_layers', ['id' => $incubationLayer->id]);
        $this->assertDatabaseHas('inventory_cost_layers', [
            'id' => $flockLayer->id,
            'remaining_quantity' => 1,
            'remaining_cost' => 100,
        ]);
        $this->assertDatabaseHas('parent_flocks', [
            'id' => $flock->id,
            'current_male_quantity' => 1,
        ]);
        $this->assertDatabaseHas('egg_stocks', [
            'id' => $stock->id,
            'good_available' => 0,
            'damaged' => 0,
        ]);

        app(SafeRecordDeletionService::class)->delete($flock);

        $this->assertDatabaseMissing('parent_flocks', ['id' => $flock->id]);
        $this->assertDatabaseMissing('egg_stocks', ['id' => $stock->id]);
        $this->assertDatabaseMissing('parent_flock_movements', ['parent_flock_id' => $flock->id]);
        $this->assertDatabaseMissing('inventory_cost_layers', ['id' => $flockLayer->id]);
    }

    public function test_daily_flock_record_cascades_later_incubation_and_reverses_egg_stock(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Later egg movement test flock',
            'initial_male_quantity' => 1,
            'initial_female_quantity' => 2,
        ]);
        $record = FlockDailyRecord::query()->create([
            'parent_flock_id' => $flock->id,
            'recorded_on' => today()->subDay(),
            'health_status' => ParentFlock::HEALTHY,
            'eggs_collected' => 1,
            'good_eggs' => 1,
        ]);
        $incubation = IncubationBatch::query()->create([
            'parent_flock_id' => $flock->id,
            'eggs_set' => 1,
            'incubated_on' => today(),
            'expected_hatch_on' => today()->addDays(21),
            'status' => IncubationBatch::STATUS_INCUBATING,
        ]);
        $stock = EggStock::query()->create([
            'parent_flock_id' => $flock->id,
            'good_available' => 0,
            'reserved_for_hatching' => 1,
        ]);
        EggInventoryMovement::query()->create([
            'parent_flock_id' => $flock->id,
            'movement_type' => EggInventoryMovement::TYPE_COLLECTION,
            'good_delta' => 1,
            'source_type' => FlockDailyRecord::class,
            'source_id' => $record->id,
        ]);
        $layer = InventoryCostLayer::query()->create([
            'inventory_type' => 'egg_stock',
            'inventory_id' => $stock->id,
            'layer_type' => 'egg_collection',
            'acquired_on' => today()->subDay(),
            'quantity' => 1,
            'remaining_quantity' => 0,
            'total_cost' => 10,
            'remaining_cost' => 0,
            'source_type' => FlockDailyRecord::class,
            'source_id' => $record->id,
        ]);
        InventoryCostMovement::query()->create([
            'inventory_cost_layer_id' => $layer->id,
            'inventory_type' => 'egg_stock',
            'inventory_id' => $stock->id,
            'movement_type' => 'egg_collection',
            'quantity_delta' => 1,
            'cost_delta' => 10,
            'moved_on' => today()->subDay(),
            'source_type' => FlockDailyRecord::class,
            'source_id' => $record->id,
        ]);
        EggInventoryMovement::query()->create([
            'parent_flock_id' => $flock->id,
            'movement_type' => EggInventoryMovement::TYPE_INCUBATION_RESERVE,
            'good_delta' => -1,
            'reserved_delta' => 1,
            'source_type' => IncubationBatch::class,
            'source_id' => $incubation->id,
        ]);

        app(SafeRecordDeletionService::class)->delete($record);

        $this->assertDatabaseMissing('flock_daily_records', ['id' => $record->id]);
        $this->assertDatabaseMissing('incubation_batches', ['id' => $incubation->id]);
        $this->assertDatabaseHas('egg_stocks', [
            'id' => $stock->id,
            'good_available' => 0,
            'reserved_for_hatching' => 0,
        ]);
    }

    public function test_parent_flock_deletion_cascades_its_daily_record_and_opening_stock(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Parent delete test flock',
            'initial_male_quantity' => 1,
            'initial_female_quantity' => 2,
        ]);
        $record = FlockDailyRecord::query()->create([
            'parent_flock_id' => $flock->id,
            'recorded_on' => today(),
            'health_status' => ParentFlock::HEALTHY,
        ]);

        app(SafeRecordDeletionService::class)->delete($flock);

        $this->assertDatabaseMissing('parent_flocks', ['id' => $flock->id]);
        $this->assertDatabaseMissing('flock_daily_records', ['id' => $record->id]);
        $this->assertDatabaseMissing('parent_flock_movements', ['parent_flock_id' => $flock->id]);
    }

    public function test_deleting_transaction_cascades_its_reversal(): void
    {
        $transaction = FarmTransaction::query()->create([
            'transaction_type' => FarmTransaction::TYPE_EXPENSE,
            'category' => FarmTransaction::EXPENSE_ELECTRICITY,
            'amount' => 100,
            'transaction_on' => today(),
        ]);

        FarmTransaction::query()->create([
            'transaction_type' => FarmTransaction::TYPE_INCOME,
            'category' => FarmTransaction::INCOME_OTHER,
            'amount' => 100,
            'transaction_on' => today(),
            'reversal_of_id' => $transaction->id,
        ]);

        $reversal = FarmTransaction::query()->where('reversal_of_id', $transaction->id)->firstOrFail();

        app(SafeRecordDeletionService::class)->delete($transaction);

        $this->assertDatabaseMissing('farm_transactions', ['id' => $transaction->id]);
        $this->assertDatabaseMissing('farm_transactions', ['id' => $reversal->id]);
    }
}

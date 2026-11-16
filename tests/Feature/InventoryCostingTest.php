<?php

namespace Tests\Feature;

use App\Models\ChickBatch;
use App\Models\EggStock;
use App\Models\FlockDailyRecord;
use App\Models\InventoryCostLayer;
use App\Models\InventoryCostMovement;
use App\Models\ParentFlock;
use App\Models\Sale;
use App\Services\EggInventoryService;
use App\Services\InventoryCostService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryCostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_valuation_reclassifies_unvalued_quantity_and_sets_its_cost(): void
    {
        $batch = $this->createBatch('Opening batch', 12);
        $costs = app(InventoryCostService::class);
        $costs->receive(
            'chick_batch',
            $batch->id,
            null,
            12,
            null,
            today()->toDateString(),
            'opening_unvalued',
            $batch,
        );

        $this->assertTrue($costs->needsOpeningValuation('chick_batch', $batch->id));
        $this->assertSame(60.0, $costs->valueOpeningStock('chick_batch', $batch->id, null, 5, today()->toDateString()));
        $this->assertFalse($costs->needsOpeningValuation('chick_batch', $batch->id));
        $this->assertSame(60.0, $costs->currentCost('chick_batch', $batch->id));
        $this->assertSame(12.0, (float) InventoryCostMovement::query()
            ->where('inventory_type', 'chick_batch')
            ->where('inventory_id', $batch->id)
            ->sum('quantity_delta'));
        $this->assertSame(0.0, (float) InventoryCostLayer::query()
            ->where('inventory_type', 'chick_batch')
            ->where('inventory_id', $batch->id)
            ->where('layer_type', 'opening_unvalued')
            ->sum('remaining_quantity'));
    }

    public function test_opening_parent_flock_valuation_can_override_remaining_egg_units(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Mature opening flock',
            'initial_male_quantity' => 0,
            'initial_female_quantity' => 4,
        ]);
        $costs = app(InventoryCostService::class);
        $costs->receive('parent_flock', $flock->id, 'female', 4, null, today()->toDateString(), 'opening_unvalued', $flock);

        $costs->valueOpeningStock('parent_flock', $flock->id, 'female', 75, today()->toDateString(), 1200);

        $this->assertSame(300.0, $costs->currentCost('parent_flock', $flock->id, 'female'));
        $this->assertSame(4800.0, (float) InventoryCostLayer::query()
            ->where('inventory_type', 'parent_flock')
            ->where('inventory_id', $flock->id)
            ->where('gender', 'female')
            ->where('layer_type', 'opening_valuation')
            ->value('remaining_production_units'));
    }

    public function test_production_cost_cannot_be_added_after_a_later_dated_stock_movement(): void
    {
        $batch = $this->createBatch('Chronology batch', 10);
        $costs = app(InventoryCostService::class);
        $costs->receive(
            'chick_batch',
            $batch->id,
            null,
            10,
            100,
            today()->subDay()->toDateString(),
            'purchase',
            $batch,
        );
        $costs->consume(
            'chick_batch',
            $batch->id,
            null,
            1,
            today()->toDateString(),
            'sale',
            $batch,
        );

        $this->expectException(ValidationException::class);
        $costs->addProductionCost('chick_batch', $batch->id, null, 20, today()->subDay()->toDateString(), $batch, 'Feed usage.');
    }

    public function test_breeder_purchase_cost_is_allocated_to_eggs_by_remaining_production_units(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Production-cost flock',
            'initial_male_quantity' => 1,
            'initial_female_quantity' => 5,
        ]);
        $costs = app(InventoryCostService::class);
        $costs->receive('parent_flock', $flock->id, 'male', 1, 100, today()->toDateString(), 'purchase', $flock);
        $costs->receive('parent_flock', $flock->id, 'female', 5, 500, today()->toDateString(), 'purchase', $flock);

        $allocated = $costs->allocateBreederCostToEggs($flock->id, 100, today()->toDateString(), $flock);

        $this->assertSame(4.0, $allocated);
        $this->assertSame(
            596.0,
            $costs->currentCost('parent_flock', $flock->id, 'male')
                + $costs->currentCost('parent_flock', $flock->id, 'female'),
        );
        $this->assertSame(15000.0 - 100, (float) InventoryCostLayer::query()
            ->where('inventory_type', 'parent_flock')
            ->where('inventory_id', $flock->id)
            ->where('gender', 'female')
            ->sum('remaining_production_units'));
        $this->assertSame(4.0, (float) InventoryCostMovement::query()
            ->where('inventory_type', 'egg_pool')
            ->where('inventory_id', $flock->id)
            ->sum('cost_delta'));
    }

    public function test_breeder_cost_flows_through_egg_collection_to_sale_cogs(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Egg cost flow flock',
            'initial_male_quantity' => 1,
            'initial_female_quantity' => 5,
        ]);
        $costs = app(InventoryCostService::class);
        $costs->receive('parent_flock', $flock->id, 'male', 1, 100, today()->toDateString(), 'purchase', $flock);
        $costs->receive('parent_flock', $flock->id, 'female', 5, 500, today()->toDateString(), 'purchase', $flock);
        $record = FlockDailyRecord::query()->create([
            'parent_flock_id' => $flock->id,
            'recorded_on' => today(),
            'male_mortality' => 0,
            'female_mortality' => 0,
            'eggs_collected' => 100,
            'good_eggs' => 100,
            'damaged_eggs' => 0,
            'feed_consumed_kg' => 0,
            'health_status' => ParentFlock::HEALTHY,
        ]);

        app(EggInventoryService::class)->recordCollection($record);
        $stock = EggStock::query()->where('parent_flock_id', $flock->id)->firstOrFail();
        $this->assertSame(4.0, $costs->currentCost('egg_stock', $stock->id));
        $sale = app(SaleService::class)->create([
            'product_type' => Sale::PRODUCT_EGG,
            'parent_flock_id' => $flock->id,
            'quantity' => 25,
            'unit_price' => 1,
            'paid_amount' => 25,
            'payment_method' => 'cash',
            'sold_on' => today()->toDateString(),
        ]);

        $this->assertSame(1.0, (float) $sale->fresh()->cost_of_goods_sold);
        $this->assertSame(3.0, $costs->currentCost('egg_stock', $stock->id));
        $this->assertSame(
            596.0,
            $costs->currentCost('parent_flock', $flock->id, 'male')
                + $costs->currentCost('parent_flock', $flock->id, 'female'),
        );
    }

    private function createBatch(string $name, int $quantity): ChickBatch
    {
        return ChickBatch::query()->create([
            'name' => $name,
            'source' => ChickBatch::SOURCE_PURCHASED,
            'hatch_date' => today()->subDays(2),
            'initial_quantity' => $quantity,
        ]);
    }
}

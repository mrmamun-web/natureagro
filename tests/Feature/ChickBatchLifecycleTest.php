<?php

namespace Tests\Feature;

use App\Models\ChickBatch;
use App\Models\ChickBatchMovement;
use App\Models\InventoryCostLayer;
use App\Models\ParentFlock;
use App\Models\Poultry;
use App\Services\ChickBatchService;
use App\Services\InventoryCostService;
use App\Services\ParentFlockService;
use App\Services\PoultryStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ChickBatchLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_mortality_and_flock_transfer_deduct_batch_stock_and_keep_history(): void
    {
        $batch = $this->createBatch('Lifecycle batch', 15);
        $flock = ParentFlock::query()->create([
            'name' => 'Grower flock',
            'initial_male_quantity' => 1,
            'initial_female_quantity' => 1,
        ]);
        $this->valueFlock($flock);
        $service = app(ChickBatchService::class);

        $service->recordMortality($batch, [
            'quantity' => 2,
            'moved_on' => today()->toDateString(),
        ]);
        $service->transfer($batch, [
            'target_type' => 'parent_flock',
            'target_parent_flock_id' => $flock->id,
            'gender' => 'female',
            'quantity' => 5,
            'moved_on' => today()->toDateString(),
        ]);

        $this->assertSame(8, $batch->fresh()->current_quantity);
        $this->assertSame(6, $flock->fresh()->current_female_quantity);
        $this->assertDatabaseHas('chick_batch_movements', [
            'chick_batch_id' => $batch->id,
            'movement_type' => ChickBatchMovement::TYPE_MORTALITY,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('chick_batch_movements', [
            'chick_batch_id' => $batch->id,
            'movement_type' => ChickBatchMovement::TYPE_TRANSFER,
            'target_parent_flock_id' => $flock->id,
            'quantity' => 5,
        ]);
    }

    public function test_transfer_and_archive_reject_unaccounted_batch_stock(): void
    {
        $batch = $this->createBatch('Recent batch', 4);
        $target = $this->createBatch('Target batch', 3);
        $service = app(ChickBatchService::class);

        try {
            $service->transfer($batch, [
                'target_type' => 'chick_batch',
                'target_chick_batch_id' => $target->id,
                'quantity' => 5,
                'moved_on' => today()->toDateString(),
            ]);
            $this->fail('A transfer exceeding available birds should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.quantity', $exception->errors());
        }

        $this->assertSame(4, $batch->fresh()->current_quantity);
        $this->assertSame(3, $target->fresh()->current_quantity);

        try {
            $service->archive($batch);
            $this->fail('A batch with live stock should not be archived.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.status', $exception->errors());
        }
    }

    public function test_chick_batch_transfer_history_links_the_source_batch(): void
    {
        $source = $this->createBatch('Transfer source', 8);
        $target = $this->createBatch('Transfer target', 2);

        app(ChickBatchService::class)->transfer($source, [
            'target_type' => 'chick_batch',
            'target_chick_batch_id' => $target->id,
            'quantity' => 3,
            'moved_on' => today()->toDateString(),
        ]);

        $this->assertSame(5, $source->fresh()->current_quantity);
        $this->assertSame(5, $target->fresh()->current_quantity);
        $this->assertDatabaseHas('chick_batch_movements', [
            'chick_batch_id' => $target->id,
            'source_chick_batch_id' => $source->id,
            'movement_type' => ChickBatchMovement::TYPE_TRANSFER_RECEIVED,
            'quantity' => 3,
        ]);
        $this->assertSame(
            $source->hatch_date->toDateString(),
            InventoryCostLayer::query()
                ->where('inventory_type', 'chick_batch')
                ->where('inventory_id', $target->id)
                ->where('layer_type', 'transfer_in')
                ->firstOrFail()
                ->acquired_on
                ->toDateString(),
        );
    }

    public function test_parent_flock_and_general_poultry_transfers_preserve_both_sides_of_history(): void
    {
        $sourceFlock = ParentFlock::query()->create([
            'name' => 'Transfer source flock',
            'initial_male_quantity' => 2,
            'initial_female_quantity' => 8,
        ]);
        $targetFlock = ParentFlock::query()->create([
            'name' => 'Transfer target flock',
            'initial_male_quantity' => 0,
            'initial_female_quantity' => 0,
        ]);
        $poultry = Poultry::query()->create([
            'name' => 'General chicken inventory',
            'type' => Poultry::TYPE_CHICKEN,
            'initial_quantity' => 1,
        ]);
        app(InventoryCostService::class)->receive(
            'poultry',
            $poultry->id,
            null,
            1,
            10,
            today()->toDateString(),
            'purchase',
            $poultry,
        );
        $this->valueFlock($sourceFlock);
        $this->valueFlock($targetFlock);

        app(ParentFlockService::class)->transferToPoultryInventory($sourceFlock, [
            'gender' => 'female',
            'quantity' => 3,
            'target_poultry_id' => $poultry->id,
            'moved_on' => today()->toDateString(),
        ]);
        app(PoultryStockService::class)->transferToParentFlock($poultry, [
            'quantity' => 2,
            'target_parent_flock_id' => $targetFlock->id,
            'moved_on' => today()->toDateString(),
        ]);

        $this->assertSame(5, $sourceFlock->fresh()->current_female_quantity);
        $this->assertSame(2, $targetFlock->fresh()->current_female_quantity);
        $this->assertSame(2, $poultry->fresh()->current_quantity);
        $this->assertDatabaseHas('parent_flock_movements', [
            'parent_flock_id' => $sourceFlock->id,
            'target_poultry_id' => $poultry->id,
            'movement_type' => 'transfer_out',
        ]);
        $this->assertDatabaseHas('poultry_movements', [
            'poultry_id' => $poultry->id,
            'source_parent_flock_id' => $sourceFlock->id,
            'movement_type' => 'transfer_in',
        ]);
        $this->assertDatabaseHas('parent_flock_movements', [
            'parent_flock_id' => $targetFlock->id,
            'source_poultry_id' => $poultry->id,
            'movement_type' => 'transfer_in',
        ]);
    }

    private function createBatch(string $name, int $quantity): ChickBatch
    {
        $batch = ChickBatch::query()->create([
            'name' => $name,
            'source' => ChickBatch::SOURCE_PURCHASED,
            'hatch_date' => today()->subMonths(7),
            'initial_quantity' => $quantity,
        ]);

        app(InventoryCostService::class)->receive(
            'chick_batch',
            $batch->id,
            null,
            $quantity,
            $quantity,
            $batch->hatch_date->toDateString(),
            'purchase',
            $batch,
        );

        return $batch;
    }

    private function valueFlock(ParentFlock $flock): void
    {
        $costs = app(InventoryCostService::class);
        if ($flock->current_male_quantity > 0) {
            $costs->receive('parent_flock', $flock->id, 'male', $flock->current_male_quantity, $flock->current_male_quantity, today()->toDateString(), 'purchase', $flock);
        }
        if ($flock->current_female_quantity > 0) {
            $costs->receive('parent_flock', $flock->id, 'female', $flock->current_female_quantity, $flock->current_female_quantity, today()->toDateString(), 'purchase', $flock);
        }
    }
}

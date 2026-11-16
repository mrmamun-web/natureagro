<?php

namespace Tests\Feature;

use App\Models\FlockDailyRecord;
use App\Models\IncubationBatch;
use App\Models\ParentFlock;
use App\Services\EggInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EggHatchingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_is_reserved_and_hatch_result_creates_a_chick_batch(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Deshi Parent Flock',
            'initial_male_quantity' => 5,
            'initial_female_quantity' => 30,
        ]);

        $dailyRecord = FlockDailyRecord::query()->create([
            'parent_flock_id' => $flock->id,
            'recorded_on' => '2026-11-15',
            'male_mortality' => 0,
            'female_mortality' => 0,
            'eggs_collected' => 20,
            'good_eggs' => 18,
            'damaged_eggs' => 2,
            'feed_consumed_kg' => 4,
            'health_status' => ParentFlock::HEALTHY,
        ]);

        $inventory = app(EggInventoryService::class);
        $inventory->recordCollection($dailyRecord);

        $incubationBatch = IncubationBatch::query()->create([
            'parent_flock_id' => $flock->id,
            'eggs_set' => 15,
            'incubated_on' => '2026-11-15',
            'expected_hatch_on' => '2026-11-29',
        ]);

        $inventory->reserveForIncubation($incubationBatch);

        $this->assertDatabaseHas('egg_stocks', [
            'parent_flock_id' => $flock->id,
            'good_available' => 3,
            'damaged' => 2,
            'reserved_for_hatching' => 15,
        ]);

        $chickBatch = $inventory->recordHatchOutcome($incubationBatch->id, [
            'actual_hatch_on' => '2026-11-15',
            'hatched_chicks' => 12,
            'failed_eggs' => 3,
        ]);

        $this->assertNotNull($chickBatch);
        $this->assertSame(12, $chickBatch->current_quantity);
        $this->assertDatabaseHas('egg_stocks', [
            'parent_flock_id' => $flock->id,
            'good_available' => 3,
            'reserved_for_hatching' => 0,
        ]);
        $this->assertDatabaseHas('chick_batches', [
            'source_incubation_batch_id' => $incubationBatch->id,
            'initial_quantity' => 12,
            'current_quantity' => 12,
        ]);
    }
}

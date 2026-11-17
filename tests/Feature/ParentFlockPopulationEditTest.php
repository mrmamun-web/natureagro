<?php

namespace Tests\Feature;

use App\Models\ParentFlock;
use App\Models\ParentFlockMovement;
use App\Services\ParentFlockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ParentFlockPopulationEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_opening_counts_adjusts_current_counts_and_opening_movement(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Editable flock',
            'initial_male_quantity' => 4,
            'initial_female_quantity' => 10,
        ]);
        $flock->update([
            'current_male_quantity' => 1,
            'current_female_quantity' => 7,
        ]);

        $updated = app(ParentFlockService::class)->updatePopulation($flock, [
            'name' => 'Edited flock',
            'breed' => 'Deshi',
            'initial_male_quantity' => 6,
            'initial_female_quantity' => 8,
            'status' => ParentFlock::STATUS_ACTIVE,
            'health_status' => ParentFlock::HEALTHY,
        ]);

        $this->assertSame(3, $updated->current_male_quantity);
        $this->assertSame(5, $updated->current_female_quantity);
        $this->assertDatabaseHas('parent_flock_movements', [
            'parent_flock_id' => $flock->id,
            'movement_type' => 'opening_stock',
            'quantity' => 14,
            'male_quantity' => 6,
            'female_quantity' => 8,
        ]);
    }

    public function test_editing_opening_counts_cannot_make_current_stock_negative(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Protected flock',
            'initial_male_quantity' => 5,
            'initial_female_quantity' => 2,
        ]);
        $flock->update([
            'current_male_quantity' => 3,
            'current_female_quantity' => 1,
        ]);

        try {
            app(ParentFlockService::class)->updatePopulation($flock, [
                'name' => 'Protected flock',
                'breed' => 'Deshi',
                'initial_male_quantity' => 1,
                'initial_female_quantity' => 2,
                'status' => ParentFlock::STATUS_ACTIVE,
                'health_status' => ParentFlock::HEALTHY,
            ]);
            $this->fail('The new opening count must not create negative current stock.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.initial_male_quantity', $exception->errors());
        }

        $this->assertDatabaseHas('parent_flocks', [
            'id' => $flock->id,
            'initial_male_quantity' => 5,
            'current_male_quantity' => 3,
        ]);
        $this->assertSame(
            5,
            ParentFlockMovement::query()
                ->where('parent_flock_id', $flock->id)
                ->where('movement_type', 'opening_stock')
                ->value('male_quantity'),
        );
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\FlockDailyRecords\Pages\CreateFlockDailyRecord;
use App\Models\ParentFlock;
use App\Services\InventoryCostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ParentFlockOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel('admin');
    }

    public function test_daily_mortality_is_recorded_and_deducted_from_separate_sex_stock(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Layer Parent Flock',
            'initial_male_quantity' => 8,
            'initial_female_quantity' => 42,
        ]);
        $this->valueFlock($flock);

        Livewire::test(CreateFlockDailyRecord::class)
            ->fillForm([
                'parent_flock_id' => $flock->id,
                'recorded_on' => today()->toDateString(),
                'male_mortality' => 1,
                'female_mortality' => 3,
                'good_eggs' => 28,
                'damaged_eggs' => 0,
                'feed_consumed_kg' => 6.5,
                'health_status' => ParentFlock::HEALTHY,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('parent_flocks', [
            'id' => $flock->id,
            'current_male_quantity' => 7,
            'current_female_quantity' => 39,
        ]);

        $this->assertDatabaseHas('flock_daily_records', [
            'parent_flock_id' => $flock->id,
            'male_mortality' => 1,
            'female_mortality' => 3,
            'eggs_collected' => 28,
        ]);

        $this->assertDatabaseHas('egg_stocks', [
            'parent_flock_id' => $flock->id,
            'good_available' => 28,
            'damaged' => 0,
        ]);
    }

    public function test_daily_mortality_cannot_exceed_current_sex_stock(): void
    {
        $flock = ParentFlock::query()->create([
            'name' => 'Small Parent Flock',
            'initial_male_quantity' => 2,
            'initial_female_quantity' => 6,
        ]);
        $this->valueFlock($flock);

        Livewire::test(CreateFlockDailyRecord::class)
            ->fillForm([
                'parent_flock_id' => $flock->id,
                'recorded_on' => today()->toDateString(),
                'male_mortality' => 3,
                'female_mortality' => 0,
                'good_eggs' => 0,
                'damaged_eggs' => 0,
                'feed_consumed_kg' => 0,
                'health_status' => ParentFlock::HEALTHY,
            ])
            ->call('create')
            ->assertHasFormErrors(['data.male_mortality']);

        $this->assertDatabaseHas('parent_flocks', [
            'id' => $flock->id,
            'current_male_quantity' => 2,
            'current_female_quantity' => 6,
        ]);

        $this->assertDatabaseCount('flock_daily_records', 0);
    }

    private function valueFlock(ParentFlock $flock): void
    {
        $costs = app(InventoryCostService::class);
        $costs->receive('parent_flock', $flock->id, 'male', $flock->current_male_quantity, $flock->current_male_quantity, today()->toDateString(), 'purchase', $flock);
        $costs->receive('parent_flock', $flock->id, 'female', $flock->current_female_quantity, $flock->current_female_quantity, today()->toDateString(), 'purchase', $flock);
    }
}

<?php

namespace App\Filament\Resources\FlockDailyRecords\Pages;

use App\Filament\Resources\FlockDailyRecords\FlockDailyRecordResource;
use App\Models\FlockDailyRecord;
use App\Models\ParentFlock;
use App\Services\EggInventoryService;
use App\Services\InventoryCostService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateFlockDailyRecord extends CreateRecord
{
    protected static string $resource = FlockDailyRecordResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): FlockDailyRecord {
            $flock = ParentFlock::query()
                ->lockForUpdate()
                ->findOrFail($data['parent_flock_id']);

            if ($flock->status !== ParentFlock::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'data.parent_flock_id' => 'Daily records can only be added to an active flock.',
                ]);
            }

            if ($flock->dailyRecords()->whereDate('recorded_on', $data['recorded_on'])->exists()) {
                throw ValidationException::withMessages([
                    'data.recorded_on' => 'A daily record already exists for this flock on the selected date.',
                ]);
            }

            if ($flock->dailyRecords()->whereDate('recorded_on', '>', $data['recorded_on'])->exists()) {
                throw ValidationException::withMessages([
                    'data.recorded_on' => 'Record flock production in date order so egg costs stay assigned to the correct production period.',
                ]);
            }

            $maleMortality = (int) $data['male_mortality'];
            $femaleMortality = (int) $data['female_mortality'];

            if ($maleMortality > $flock->current_male_quantity) {
                throw ValidationException::withMessages([
                    'data.male_mortality' => 'Male mortality cannot exceed the current male stock.',
                ]);
            }

            if ($femaleMortality > $flock->current_female_quantity) {
                throw ValidationException::withMessages([
                    'data.female_mortality' => 'Female mortality cannot exceed the current female stock.',
                ]);
            }

            $flock->current_male_quantity -= $maleMortality;
            $flock->current_female_quantity -= $femaleMortality;
            $flock->health_status = $data['health_status'];
            $flock->save();

            $data['eggs_collected'] = (int) $data['good_eggs'] + (int) $data['damaged_eggs'];
            $record = $flock->dailyRecords()->create($data);
            app(EggInventoryService::class)->recordCollection($record);
            $costs = app(InventoryCostService::class);
            $mortalityCost = 0.0;

            if ($maleMortality > 0) {
                $mortalityCost += $costs->consume(
                    'parent_flock',
                    $flock->id,
                    'male',
                    $maleMortality,
                    $record->recorded_on->toDateString(),
                    'mortality',
                    $record,
                    'Male parent flock mortality.',
                )['total_cost'];
            }

            if ($femaleMortality > 0) {
                $mortalityCost += $costs->consume(
                    'parent_flock',
                    $flock->id,
                    'female',
                    $femaleMortality,
                    $record->recorded_on->toDateString(),
                    'mortality',
                    $record,
                    'Female parent flock mortality.',
                )['total_cost'];
            }

            if ($maleMortality + $femaleMortality > 0) {
                $flock->movements()
                    ->where('flock_daily_record_id', $record->id)
                    ->update(['total_cost' => number_format($mortalityCost, 2, '.', '')]);
            }

            return $record;
        });
    }
}

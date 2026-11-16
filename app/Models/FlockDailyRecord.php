<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlockDailyRecord extends Model
{
    protected $fillable = [
        'parent_flock_id',
        'recorded_on',
        'male_mortality',
        'female_mortality',
        'eggs_collected',
        'good_eggs',
        'damaged_eggs',
        'feed_consumed_kg',
        'health_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'male_mortality' => 'integer',
            'female_mortality' => 'integer',
            'eggs_collected' => 'integer',
            'good_eggs' => 'integer',
            'damaged_eggs' => 'integer',
            'feed_consumed_kg' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (FlockDailyRecord $record): void {
            $male = (int) $record->male_mortality;
            $female = (int) $record->female_mortality;
            if ($male + $female === 0) {
                return;
            }

            ParentFlockMovement::query()->create([
                'parent_flock_id' => $record->parent_flock_id,
                'movement_type' => 'mortality',
                'quantity' => $male + $female,
                'male_quantity' => $male,
                'female_quantity' => $female,
                'flock_daily_record_id' => $record->id,
                'moved_on' => $record->recorded_on,
                'notes' => 'Recorded in daily flock record.',
            ]);
        });
    }

    public function parentFlock(): BelongsTo
    {
        return $this->belongsTo(ParentFlock::class);
    }
}

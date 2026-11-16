<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentFlockMovement extends Model
{
    protected $fillable = [
        'parent_flock_id',
        'movement_type',
        'quantity',
        'male_quantity',
        'female_quantity',
        'source_chick_batch_id',
        'source_poultry_id',
        'target_poultry_id',
        'sale_id',
        'total_cost',
        'flock_daily_record_id',
        'moved_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'total_cost' => 'decimal:2',
            'male_quantity' => 'integer',
            'female_quantity' => 'integer',
            'moved_on' => 'date',
        ];
    }

    public function parentFlock(): BelongsTo
    {
        return $this->belongsTo(ParentFlock::class);
    }

    public function sourceChickBatch(): BelongsTo
    {
        return $this->belongsTo(ChickBatch::class, 'source_chick_batch_id');
    }

    public function sourcePoultry(): BelongsTo
    {
        return $this->belongsTo(Poultry::class, 'source_poultry_id');
    }

    public function targetPoultry(): BelongsTo
    {
        return $this->belongsTo(Poultry::class, 'target_poultry_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function flockDailyRecord(): BelongsTo
    {
        return $this->belongsTo(FlockDailyRecord::class);
    }
}

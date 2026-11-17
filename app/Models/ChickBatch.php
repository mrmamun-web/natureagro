<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ChickBatch extends Model
{
    public const SOURCE_HATCHED = 'hatched';

    public const SOURCE_PURCHASED = 'purchased';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'batch_code',
        'name',
        'breed',
        'source',
        'source_incubation_batch_id',
        'hatch_date',
        'batch_month',
        'initial_quantity',
        'current_quantity',
        'survived_after_7_days',
        'survival_recorded_on',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'hatch_date' => 'date',
            'batch_month' => 'integer',
            'initial_quantity' => 'integer',
            'current_quantity' => 'integer',
            'survived_after_7_days' => 'integer',
            'survival_recorded_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChickBatch $batch): void {
            if ($batch->batch_month === null && $batch->hatch_date !== null) {
                $batch->batch_month = $batch->hatch_date->month;
            }

            if (blank($batch->batch_code)) {
                do {
                    $code = 'CHB-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
                } while (static::query()->where('batch_code', $code)->exists());

                $batch->batch_code = $code;
            }

            $batch->current_quantity = $batch->initial_quantity;
        });
    }

    public function getBatchMonthNameAttribute(): ?string
    {
        return IncubationBatch::monthOptions()[$this->batch_month] ?? null;
    }

    public function sourceIncubationBatch(): BelongsTo
    {
        return $this->belongsTo(IncubationBatch::class, 'source_incubation_batch_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ChickBatchMovement::class);
    }

    public function getAgeMonthsAttribute(): int
    {
        return (int) ($this->hatch_date?->diffInMonths(today()) ?? 0);
    }
}

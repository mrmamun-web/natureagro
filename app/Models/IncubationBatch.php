<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class IncubationBatch extends Model
{
    public const HATCHING_PERIOD_DAYS = 21;

    public const STATUS_INCUBATING = 'incubating';

    public const STATUS_HATCHED = 'hatched';

    protected $fillable = [
        'batch_code',
        'parent_flock_id',
        'eggs_set',
        'incubation_month',
        'hatched_chicks',
        'failed_eggs',
        'incubated_on',
        'expected_hatch_on',
        'actual_hatch_on',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'eggs_set' => 'integer',
            'hatched_chicks' => 'integer',
            'failed_eggs' => 'integer',
            'incubated_on' => 'date',
            'incubation_month' => 'integer',
            'expected_hatch_on' => 'date',
            'actual_hatch_on' => 'date',
        ];
    }

    public static function monthOptions(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn (int $month): array => [$month => Carbon::create()->month($month)->format('F')])
            ->all();
    }

    protected static function booted(): void
    {
        static::creating(function (IncubationBatch $batch): void {
            if ($batch->incubation_month === null && $batch->incubated_on !== null) {
                $batch->incubation_month = $batch->incubated_on->month;
            }

            if (blank($batch->batch_code)) {
                do {
                    $code = 'INC-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
                } while (static::query()->where('batch_code', $code)->exists());

                $batch->batch_code = $code;
            }
        });
    }

    public function getIncubationMonthNameAttribute(): ?string
    {
        return self::monthOptions()[$this->incubation_month] ?? null;
    }

    public function parentFlock(): BelongsTo
    {
        return $this->belongsTo(ParentFlock::class);
    }

    public function chickBatch(): HasOne
    {
        return $this->hasOne(ChickBatch::class, 'source_incubation_batch_id');
    }

    public function getHatchRateAttribute(): ?float
    {
        if ($this->status !== self::STATUS_HATCHED || $this->eggs_set < 1) {
            return null;
        }

        return round(($this->hatched_chicks / $this->eggs_set) * 100, 1);
    }
}

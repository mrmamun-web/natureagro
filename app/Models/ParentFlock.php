<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class ParentFlock extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const HEALTHY = 'healthy';

    public const HEALTH_MONITORING = 'monitoring';

    public const HEALTH_CRITICAL = 'critical';

    protected $fillable = [
        'flock_code',
        'name',
        'breed',
        'initial_male_quantity',
        'initial_female_quantity',
        'current_male_quantity',
        'current_female_quantity',
        'purchase_date',
        'health_status',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'initial_male_quantity' => 'integer',
            'initial_female_quantity' => 'integer',
            'current_male_quantity' => 'integer',
            'current_female_quantity' => 'integer',
            'purchase_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ParentFlock $flock): void {
            if (blank($flock->flock_code)) {
                do {
                    $code = 'PFL-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
                } while (static::query()->where('flock_code', $code)->exists());

                $flock->flock_code = $code;
            }

            $flock->current_male_quantity = $flock->initial_male_quantity;
            $flock->current_female_quantity = $flock->initial_female_quantity;
        });

        static::created(function (ParentFlock $flock): void {
            $flock->movements()->create([
                'movement_type' => 'opening_stock',
                'quantity' => $flock->initial_male_quantity + $flock->initial_female_quantity,
                'male_quantity' => $flock->initial_male_quantity,
                'female_quantity' => $flock->initial_female_quantity,
                'moved_on' => $flock->purchase_date ?? today(),
                'notes' => 'Opening parent flock record.',
            ]);
        });

        static::deleting(function (): void {
            throw new \LogicException('Parent flock history cannot be deleted.');
        });
    }

    public function dailyRecords(): HasMany
    {
        return $this->hasMany(FlockDailyRecord::class);
    }

    public function incubationBatches(): HasMany
    {
        return $this->hasMany(IncubationBatch::class);
    }

    public function eggStock(): HasOne
    {
        return $this->hasOne(EggStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ParentFlockMovement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function getCurrentQuantityAttribute(): int
    {
        return $this->current_male_quantity + $this->current_female_quantity;
    }
}

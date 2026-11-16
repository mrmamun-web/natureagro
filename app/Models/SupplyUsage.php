<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupplyUsage extends Model
{
    protected $fillable = [
        'usage_number', 'supply_product_id', 'parent_flock_id', 'chick_batch_id',
        'used_on', 'quantity', 'total_cost', 'usage_type', 'diagnosis', 'dosage',
        'administered_by', 'next_dose_on', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'used_on' => 'date',
            'next_dose_on' => 'date',
            'quantity' => 'decimal:3',
            'total_cost' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SupplyUsage $usage): void {
            if (blank($usage->usage_number)) {
                do {
                    $number = 'USE-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
                } while (static::query()->where('usage_number', $number)->exists());

                $usage->usage_number = $number;
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(SupplyProduct::class, 'supply_product_id');
    }

    public function parentFlock(): BelongsTo
    {
        return $this->belongsTo(ParentFlock::class);
    }

    public function chickBatch(): BelongsTo
    {
        return $this->belongsTo(ChickBatch::class);
    }

    public function lotAllocations(): HasMany
    {
        return $this->hasMany(SupplyUsageLot::class);
    }
}

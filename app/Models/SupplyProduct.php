<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class SupplyProduct extends Model
{
    public const TYPE_FEED = 'feed';

    public const TYPE_MEDICINE = 'medicine';

    protected $fillable = ['name', 'type', 'unit', 'description', 'minimum_stock', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'minimum_stock' => 'decimal:3',
        ];
    }

    public function lots(): HasMany
    {
        return $this->hasMany(SupplyLot::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(SupplyPurchase::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(SupplyUsage::class);
    }

    public function getStockQuantityAttribute(): float
    {
        return (float) $this->lots()->sum('remaining_quantity');
    }

    public function getUsableStockQuantityAttribute(): float
    {
        $lots = $this->lots();

        if ($this->type === self::TYPE_MEDICINE) {
            $lots->where(function ($query): void {
                $query->whereNull('expires_on')->orWhereDate('expires_on', '>=', today());
            });
        }

        return (float) $lots->sum('remaining_quantity');
    }

    protected static function booted(): void
    {
        static::updating(function (SupplyProduct $product): void {
            if (($product->isDirty('type') || $product->isDirty('unit'))
                && ($product->purchases()->exists() || $product->usages()->exists())) {
                throw ValidationException::withMessages([
                    'data.type' => 'Product type and stock unit cannot change after inventory history exists.',
                ]);
            }
        });
    }
}

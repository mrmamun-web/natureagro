<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplyLot extends Model
{
    protected $fillable = [
        'supply_product_id', 'supply_purchase_id', 'received_on', 'expires_on',
        'initial_quantity', 'remaining_quantity', 'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'received_on' => 'date',
            'expires_on' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(SupplyProduct::class, 'supply_product_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(SupplyPurchase::class, 'supply_purchase_id');
    }

    public function usageAllocations(): HasMany
    {
        return $this->hasMany(SupplyUsageLot::class);
    }
}

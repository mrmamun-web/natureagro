<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyUsageLot extends Model
{
    protected $fillable = ['supply_usage_id', 'supply_lot_id', 'quantity', 'unit_cost', 'total_cost'];

    public function usage(): BelongsTo
    {
        return $this->belongsTo(SupplyUsage::class, 'supply_usage_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(SupplyLot::class, 'supply_lot_id');
    }
}

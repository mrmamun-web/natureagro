<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupplyStockMovement extends Model
{
    protected $fillable = [
        'supply_product_id', 'supply_lot_id', 'movement_type', 'quantity_delta',
        'unit_cost', 'total_cost', 'source_type', 'source_id', 'moved_on', 'notes',
    ];

    protected function casts(): array
    {
        return ['moved_on' => 'date'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(SupplyProduct::class, 'supply_product_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(SupplyLot::class, 'supply_lot_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}

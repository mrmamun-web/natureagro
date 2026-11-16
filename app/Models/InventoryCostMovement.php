<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCostMovement extends Model
{
    protected $fillable = [
        'inventory_cost_layer_id', 'inventory_type', 'inventory_id', 'gender',
        'movement_type', 'quantity_delta', 'cost_delta', 'production_units_delta', 'moved_on',
        'source_type', 'source_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_delta' => 'decimal:3',
            'cost_delta' => 'decimal:2',
            'production_units_delta' => 'decimal:3',
            'moved_on' => 'date',
        ];
    }

    public function layer(): BelongsTo
    {
        return $this->belongsTo(InventoryCostLayer::class, 'inventory_cost_layer_id');
    }
}

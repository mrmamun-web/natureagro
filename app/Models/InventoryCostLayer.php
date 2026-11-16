<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryCostLayer extends Model
{
    protected $fillable = [
        'inventory_type', 'inventory_id', 'gender', 'layer_type', 'acquired_on',
        'quantity', 'remaining_quantity', 'total_cost', 'remaining_cost',
        'total_production_units', 'remaining_production_units',
        'source_type', 'source_id',
    ];

    protected function casts(): array
    {
        return [
            'acquired_on' => 'date',
            'quantity' => 'decimal:3',
            'remaining_quantity' => 'decimal:3',
            'total_cost' => 'decimal:2',
            'remaining_cost' => 'decimal:2',
            'total_production_units' => 'decimal:3',
            'remaining_production_units' => 'decimal:3',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryCostMovement::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EggStock extends Model
{
    protected $fillable = [
        'parent_flock_id',
        'good_available',
        'damaged',
        'reserved_for_hatching',
    ];

    protected function casts(): array
    {
        return [
            'good_available' => 'integer',
            'damaged' => 'integer',
            'reserved_for_hatching' => 'integer',
        ];
    }

    public function parentFlock(): BelongsTo
    {
        return $this->belongsTo(ParentFlock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(EggInventoryMovement::class, 'parent_flock_id', 'parent_flock_id');
    }
}

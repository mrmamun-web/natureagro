<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoultryMovement extends Model
{
    public const TYPE_SALE = 'sale';

    public const TYPE_MORTALITY = 'mortality';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    protected $fillable = [
        'poultry_id',
        'movement_type',
        'quantity',
        'source_chick_batch_id',
        'source_parent_flock_id',
        'sale_id',
        'total_cost',
        'moved_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'total_cost' => 'decimal:2',
            'moved_on' => 'date',
        ];
    }

    public function poultry(): BelongsTo
    {
        return $this->belongsTo(Poultry::class);
    }

    public function sourceChickBatch(): BelongsTo
    {
        return $this->belongsTo(ChickBatch::class, 'source_chick_batch_id');
    }

    public function sourceParentFlock(): BelongsTo
    {
        return $this->belongsTo(ParentFlock::class, 'source_parent_flock_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}

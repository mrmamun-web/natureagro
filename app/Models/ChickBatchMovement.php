<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChickBatchMovement extends Model
{
    public const TYPE_MORTALITY = 'mortality';

    public const TYPE_TRANSFER = 'transfer';

    public const TYPE_TRANSFER_RECEIVED = 'transfer_received';

    public const TYPE_SALE = 'sale';

    public const TYPE_ARCHIVE = 'archive';

    protected $fillable = [
        'chick_batch_id',
        'movement_type',
        'quantity',
        'gender',
        'source_chick_batch_id',
        'target_chick_batch_id',
        'target_parent_flock_id',
        'target_poultry_id',
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

    public function chickBatch(): BelongsTo
    {
        return $this->belongsTo(ChickBatch::class);
    }

    public function targetChickBatch(): BelongsTo
    {
        return $this->belongsTo(ChickBatch::class, 'target_chick_batch_id');
    }

    public function sourceChickBatch(): BelongsTo
    {
        return $this->belongsTo(ChickBatch::class, 'source_chick_batch_id');
    }

    public function targetParentFlock(): BelongsTo
    {
        return $this->belongsTo(ParentFlock::class, 'target_parent_flock_id');
    }

    public function targetPoultry(): BelongsTo
    {
        return $this->belongsTo(Poultry::class, 'target_poultry_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}

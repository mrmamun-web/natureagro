<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EggInventoryMovement extends Model
{
    public const TYPE_COLLECTION = 'collection';

    public const TYPE_LEGACY_COLLECTION = 'legacy_collection';

    public const TYPE_INCUBATION_RESERVE = 'incubation_reserve';

    public const TYPE_HATCH_OUTCOME = 'hatch_outcome';

    protected $fillable = [
        'parent_flock_id',
        'movement_type',
        'good_delta',
        'damaged_delta',
        'reserved_delta',
        'source_type',
        'source_id',
        'total_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'good_delta' => 'integer',
            'damaged_delta' => 'integer',
            'reserved_delta' => 'integer',
            'total_cost' => 'decimal:2',
        ];
    }

    public function parentFlock(): BelongsTo
    {
        return $this->belongsTo(ParentFlock::class);
    }
}

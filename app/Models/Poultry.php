<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Poultry extends Model
{
    public const TYPE_CHICKEN = 'chicken';

    public const TYPE_ROOSTER = 'rooster';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SOLD = 'sold';

    public const STATUS_DECEASED = 'deceased';

    protected $fillable = [
        'poultry_code',
        'name',
        'type',
        'breed',
        'initial_quantity',
        'current_quantity',
        'purchase_date',
        'unit_purchase_price',
        'date_of_birth',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'initial_quantity' => 'integer',
            'current_quantity' => 'integer',
            'purchase_date' => 'date',
            'date_of_birth' => 'date',
            'unit_purchase_price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Poultry $poultry): void {
            if (blank($poultry->poultry_code)) {
                do {
                    $code = 'POU-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
                } while (static::query()->where('poultry_code', $code)->exists());

                $poultry->poultry_code = $code;
            }

            // Initial stock is the starting current stock.
            $poultry->current_quantity = $poultry->initial_quantity;
        });

        static::created(function (Poultry $poultry): void {
            $poultry->movements()->create([
                'movement_type' => 'opening_stock',
                'quantity' => $poultry->initial_quantity,
                'moved_on' => $poultry->purchase_date ?? today(),
                'notes' => 'Opening poultry inventory record.',
            ]);
        });

    }

    public function movements(): HasMany
    {
        return $this->hasMany(PoultryMovement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeChickens(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_CHICKEN);
    }

    public function scopeRoosters(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_ROOSTER);
    }
}

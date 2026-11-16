<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class SupplyPurchase extends Model
{
    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_PARTIAL = 'partial';

    public const PAYMENT_DUE = 'due';

    protected $fillable = [
        'purchase_number', 'supplier_id', 'supply_product_id', 'purchased_on',
        'quantity', 'unit_cost', 'total_amount', 'paid_amount', 'due_amount',
        'payment_status', 'payment_method', 'supplier_invoice', 'notes',
    ];

    protected function casts(): array
    {
        return ['purchased_on' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(function (SupplyPurchase $purchase): void {
            if (blank($purchase->purchase_number)) {
                do {
                    $number = 'PUR-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
                } while (static::query()->where('purchase_number', $number)->exists());

                $purchase->purchase_number = $number;
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(SupplyProduct::class, 'supply_product_id');
    }

    public function lot(): HasOne
    {
        return $this->hasOne(SupplyLot::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplyPurchasePayment::class);
    }
}

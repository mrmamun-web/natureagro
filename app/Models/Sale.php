<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Sale extends Model
{
    public const PRODUCT_EGG = 'egg';

    public const PRODUCT_PARENT_HEN = 'parent_hen';

    public const PRODUCT_PARENT_ROOSTER = 'parent_rooster';

    public const PRODUCT_POULTRY = 'poultry';

    public const PRODUCT_CHICK = 'chick';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_PARTIAL = 'partial';

    public const PAYMENT_DUE = 'due';

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'product_type',
        'parent_flock_id',
        'poultry_id',
        'chick_batch_id',
        'quantity',
        'unit_weight_kg',
        'unit_price',
        'discount_amount',
        'total_amount',
        'cost_of_goods_sold',
        'paid_amount',
        'due_amount',
        'payment_status',
        'payment_method',
        'sold_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_weight_kg' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'cost_of_goods_sold' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_amount' => 'decimal:2',
            'sold_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Sale $sale): void {
            if (blank($sale->invoice_number)) {
                do {
                    $invoice = 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
                } while (static::query()->where('invoice_number', $invoice)->exists());

                $sale->invoice_number = $invoice;
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function parentFlock(): BelongsTo
    {
        return $this->belongsTo(ParentFlock::class);
    }

    public function poultry(): BelongsTo
    {
        return $this->belongsTo(Poultry::class);
    }

    public function chickBatch(): BelongsTo
    {
        return $this->belongsTo(ChickBatch::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }
}

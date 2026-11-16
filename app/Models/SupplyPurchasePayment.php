<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyPurchasePayment extends Model
{
    protected $fillable = ['amount', 'paid_on', 'payment_method', 'reference', 'notes'];

    protected function casts(): array
    {
        return ['paid_on' => 'date'];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(SupplyPurchase::class, 'supply_purchase_id');
    }
}

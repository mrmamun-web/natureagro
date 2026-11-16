<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FarmTransaction extends Model
{
    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    public const INCOME_OTHER = 'other_income';

    public const EXPENSE_LABOUR = 'labour';

    public const EXPENSE_ELECTRICITY = 'electricity';

    public const EXPENSE_TRANSPORT = 'transport';

    public const EXPENSE_MAINTENANCE = 'maintenance';

    public const EXPENSE_MISCELLANEOUS = 'miscellaneous';

    protected $fillable = [
        'transaction_type',
        'category',
        'amount',
        'transaction_on',
        'payment_method',
        'reference',
        'reversal_of_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_on' => 'date',
        ];
    }

    public function reversedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }
}

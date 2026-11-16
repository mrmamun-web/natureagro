<?php

namespace App\Services;

use App\Models\FarmTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FarmTransactionService
{
    public function reverse(FarmTransaction $transaction): FarmTransaction
    {
        return DB::transaction(function () use ($transaction): FarmTransaction {
            $locked = FarmTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($locked->reversal_of_id !== null || $locked->reversal()->exists()) {
                throw ValidationException::withMessages([
                    'data.transaction' => 'This transaction has already been reversed or is itself a reversal.',
                ]);
            }

            return FarmTransaction::query()->create([
                'transaction_type' => $locked->transaction_type === FarmTransaction::TYPE_INCOME
                    ? FarmTransaction::TYPE_EXPENSE
                    : FarmTransaction::TYPE_INCOME,
                'category' => $locked->category,
                'amount' => $locked->amount,
                'transaction_on' => today(),
                'payment_method' => $locked->payment_method,
                'reference' => $locked->reference,
                'reversal_of_id' => $locked->id,
                'notes' => 'Reversal of transaction #'.$locked->id,
            ]);
        });
    }
}

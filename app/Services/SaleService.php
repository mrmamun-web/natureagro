<?php

namespace App\Services;

use App\Models\ChickBatch;
use App\Models\ChickBatchMovement;
use App\Models\EggInventoryMovement;
use App\Models\EggStock;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleService
{
    private const MAX_AMOUNT = 9999999999.99;

    public function create(array $data): Sale
    {
        $data = $this->validateSaleData($data);

        return DB::transaction(function () use ($data): Sale {
            $productType = $data['product_type'] ?? null;
            $supportedProducts = [
                Sale::PRODUCT_EGG,
                Sale::PRODUCT_CHICK,
            ];

            if (! in_array($productType, $supportedProducts, true)) {
                throw ValidationException::withMessages([
                    'data.product_type' => 'Select a supported sale product.',
                ]);
            }

            $quantityValue = $data['quantity'] ?? null;
            if (! is_numeric($quantityValue) || (float) $quantityValue !== (float) (int) $quantityValue || (int) $quantityValue < 1 || (int) $quantityValue > 2147483647) {
                throw ValidationException::withMessages([
                    'data.quantity' => 'Enter a whole quantity greater than zero.',
                ]);
            }

            $quantity = (int) $quantityValue;
            $unitPrice = $this->toAmount($data['unit_price'] ?? null, 'data.unit_price');
            $discount = $this->toAmount($data['discount_amount'] ?? 0, 'data.discount_amount');
            $paid = $this->toAmount($data['paid_amount'] ?? 0, 'data.paid_amount');

            if ($unitPrice <= 0 || $discount < 0 || $paid < 0) {
                throw ValidationException::withMessages([
                    'data.unit_price' => 'Price must be positive.',
                ]);
            }

            $subtotal = round($quantity * $unitPrice, 2);
            if (! is_finite($subtotal) || $subtotal > self::MAX_AMOUNT) {
                throw ValidationException::withMessages([
                    'data.quantity' => 'Sale total exceeds the supported invoice amount.',
                ]);
            }

            if ($discount > $subtotal) {
                throw ValidationException::withMessages([
                    'data.discount_amount' => 'Discount cannot exceed the sale subtotal.',
                ]);
            }

            $total = round($subtotal - $discount, 2);
            if ($paid > $total) {
                throw ValidationException::withMessages([
                    'data.paid_amount' => 'Paid amount cannot exceed the invoice total.',
                ]);
            }

            $source = $this->lockAndValidateStock($data, $quantity);

            $sale = Sale::query()->create([
                'customer_id' => $data['customer_id'] ?? null,
                'product_type' => $productType,
                'parent_flock_id' => $productType === Sale::PRODUCT_EGG ? ($data['parent_flock_id'] ?? null) : null,
                'chick_batch_id' => $productType === Sale::PRODUCT_CHICK ? ($data['chick_batch_id'] ?? null) : null,
                'quantity' => $quantity,
                'unit_weight_kg' => null,
                'unit_price' => $this->money($unitPrice),
                'discount_amount' => $this->money($discount),
                'total_amount' => $this->money($total),
                'paid_amount' => $this->money($paid),
                'due_amount' => $this->money($total - $paid),
                'payment_status' => $this->paymentStatus($total, $paid),
                'payment_method' => $paid > 0 ? ($data['payment_method'] ?? null) : null,
                'sold_on' => $data['sold_on'],
                'notes' => $data['notes'] ?? null,
            ]);

            $costOfGoodsSold = $this->deductStock($data, $source, $quantity, $sale);
            $sale->update(['cost_of_goods_sold' => $this->money($costOfGoodsSold)]);

            if ($paid > 0) {
                SalePayment::query()->create([
                    'sale_id' => $sale->id,
                    'amount' => $this->money($paid),
                    'paid_on' => $data['sold_on'],
                    'payment_method' => $data['payment_method'],
                ]);
            }

            return $sale;
        });
    }

    public function recordPayment(Sale $sale, array $data): Sale
    {
        $data = $this->validatePaymentData($data);

        return DB::transaction(function () use ($sale, $data): Sale {
            $lockedSale = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            $amount = $this->toAmount($data['amount'] ?? null, 'data.amount');
            $due = (float) $lockedSale->due_amount;

            if ($amount <= 0 || $amount > $due) {
                throw ValidationException::withMessages([
                    'data.amount' => 'Payment must be greater than zero and cannot exceed the outstanding due.',
                ]);
            }

            $lockedSale->payments()->create([
                'amount' => $this->money($amount),
                'paid_on' => $data['paid_on'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $paid = round((float) $lockedSale->paid_amount + $amount, 2);
            $total = (float) $lockedSale->total_amount;
            $lockedSale->paid_amount = $this->money($paid);
            $lockedSale->due_amount = $this->money(round($total - $paid, 2));
            $lockedSale->payment_status = $this->paymentStatus($total, $paid);
            $lockedSale->save();

            return $lockedSale;
        });
    }

    private function lockAndValidateStock(array $data, int $quantity): object
    {
        return match ($data['product_type']) {
            Sale::PRODUCT_EGG => $this->lockEggStock($data, $quantity),
            Sale::PRODUCT_CHICK => $this->lockChickBatch($data, $quantity),
            default => throw ValidationException::withMessages([
                'data.product_type' => 'Select a supported sale product.',
            ]),
        };
    }

    private function lockEggStock(array $data, int $quantity): EggStock
    {
        $stock = EggStock::query()
            ->where('parent_flock_id', $data['parent_flock_id'] ?? 0)
            ->lockForUpdate()
            ->first();

        if ($stock === null || $stock->good_available < $quantity) {
            throw ValidationException::withMessages([
                'data.quantity' => 'Available good egg stock is not enough for this sale.',
            ]);
        }

        return $stock;
    }

    private function lockChickBatch(array $data, int $quantity): ChickBatch
    {
        $batch = ChickBatch::query()->lockForUpdate()->find($data['chick_batch_id'] ?? 0);

        if ($batch === null
            || $batch->source !== ChickBatch::SOURCE_HATCHED
            || $batch->status !== ChickBatch::STATUS_ACTIVE
            || $batch->current_quantity < $quantity) {
            throw ValidationException::withMessages([
                'data.chick_batch_id' => 'Select an active hatch-produced batch with enough live stock.',
            ]);
        }

        return $batch;
    }

    private function deductStock(array $data, object $source, int $quantity, Sale $sale): float
    {
        return match ($data['product_type']) {
            Sale::PRODUCT_EGG => $this->deductEggs($source, $quantity, $sale),
            Sale::PRODUCT_CHICK => $this->deductChickBatch($source, $quantity, $sale),
        };
    }

    private function deductEggs(EggStock $stock, int $quantity, Sale $sale): float
    {
        $cost = app(InventoryCostService::class)->consume(
            'egg_stock',
            $stock->id,
            null,
            $quantity,
            $sale->sold_on->toDateString(),
            'sale',
            $sale,
            'FIFO cost of eggs sold.',
        )['total_cost'];
        $stock->decrement('good_available', $quantity);

        EggInventoryMovement::query()->create([
            'parent_flock_id' => $stock->parent_flock_id,
            'movement_type' => 'sale',
            'good_delta' => -$quantity,
            'total_cost' => number_format($cost, 2, '.', ''),
            'source_type' => Sale::class,
            'source_id' => $sale->id,
            'notes' => 'Egg sale '.$sale->invoice_number,
        ]);

        return $cost;
    }

    private function deductChickBatch(ChickBatch $batch, int $quantity, Sale $sale): float
    {
        $cost = app(InventoryCostService::class)->consume(
            'chick_batch',
            $batch->id,
            null,
            $quantity,
            $sale->sold_on->toDateString(),
            'sale',
            $sale,
            'FIFO cost of chicks sold.',
        )['total_cost'];
        $batch->decrement('current_quantity', $quantity);
        $batch->movements()->create([
            'movement_type' => ChickBatchMovement::TYPE_SALE,
            'quantity' => $quantity,
            'total_cost' => number_format($cost, 2, '.', ''),
            'sale_id' => $sale->id,
            'moved_on' => $sale->sold_on,
            'notes' => 'Sale '.$sale->invoice_number,
        ]);

        return $cost;
    }

    private function paymentStatus(float $total, float $paid): string
    {
        if ($paid >= $total) {
            return Sale::PAYMENT_PAID;
        }

        return $paid > 0 ? Sale::PAYMENT_PARTIAL : Sale::PAYMENT_DUE;
    }

    private function toAmount(mixed $amount, string $field): float
    {
        if (! is_numeric($amount) || ! is_finite((float) $amount) || abs((float) $amount) > self::MAX_AMOUNT) {
            throw ValidationException::withMessages([
                $field => 'Enter a valid monetary amount within the supported range.',
            ]);
        }

        return round((float) $amount, 2);
    }

    private function validateSaleData(array $data): array
    {
        $productType = $data['product_type'] ?? null;
        $requiresFlock = $productType === Sale::PRODUCT_EGG;
        $paidAmount = is_numeric($data['paid_amount'] ?? null) ? (float) $data['paid_amount'] : 0;

        return $this->validateData($data, [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'product_type' => ['required', Rule::in([
                Sale::PRODUCT_EGG,
                Sale::PRODUCT_CHICK,
            ])],
            'parent_flock_id' => [Rule::requiredIf($requiresFlock), 'nullable', 'integer', 'exists:parent_flocks,id'],
            'chick_batch_id' => [Rule::requiredIf($productType === Sale::PRODUCT_CHICK), 'nullable', 'integer', 'exists:chick_batches,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'unit_weight_kg' => ['prohibited'],
            'unit_price' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'paid_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'payment_method' => [Rule::requiredIf($paidAmount > 0), 'nullable', Rule::in(['cash', 'bkash', 'nagad', 'bank'])],
            'sold_on' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function validatePaymentData(array $data): array
    {
        return $this->validateData($data, [
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', Rule::in(['cash', 'bkash', 'nagad', 'bank'])],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function validateData(array $data, array $rules): array
    {
        try {
            return Validator::make($data, $rules)->validate();
        } catch (ValidationException $exception) {
            $errors = [];
            foreach ($exception->errors() as $field => $messages) {
                $errors['data.'.$field] = $messages;
            }

            throw ValidationException::withMessages($errors);
        }
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}

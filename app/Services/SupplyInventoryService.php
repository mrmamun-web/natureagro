<?php

namespace App\Services;

use App\Models\ChickBatch;
use App\Models\FlockDailyRecord;
use App\Models\ParentFlock;
use App\Models\Supplier;
use App\Models\SupplyLot;
use App\Models\SupplyProduct;
use App\Models\SupplyPurchase;
use App\Models\SupplyStockMovement;
use App\Models\SupplyUsage;
use App\Models\SupplyUsageLot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupplyInventoryService
{
    private const MAX_AMOUNT = 9999999999.99;

    public function createPurchase(array $data): SupplyPurchase
    {
        $data = $this->validate($data, [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'supply_product_id' => ['required', 'integer', 'exists:supply_products,id'],
            'purchased_on' => ['required', 'date', 'before_or_equal:today'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:1000000.000'],
            'unit_cost' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'paid_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'payment_method' => [Rule::requiredIf((float) ($data['paid_amount'] ?? 0) > 0), 'nullable', Rule::in(['cash', 'bkash', 'nagad', 'bank'])],
            'supplier_invoice' => ['nullable', 'string', 'max:100'],
            'expires_on' => ['nullable', 'date', 'after:purchased_on'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($data): SupplyPurchase {
            $supplier = Supplier::query()->lockForUpdate()->findOrFail($data['supplier_id']);
            $product = SupplyProduct::query()->lockForUpdate()->findOrFail($data['supply_product_id']);

            if (! $supplier->is_active) {
                $this->fail('supplier_id', 'Select an active supplier.');
            }

            if (! $product->is_active) {
                $this->fail('supply_product_id', 'Select an active feed or medicine product.');
            }

            if ($product->type === SupplyProduct::TYPE_MEDICINE && blank($data['expires_on'] ?? null)) {
                $this->fail('expires_on', 'Medicine purchases must include a lot expiry date.');
            }

            if (! in_array($product->type, [SupplyProduct::TYPE_FEED, SupplyProduct::TYPE_MEDICINE], true)) {
                $this->fail('supply_product_id', 'Only feed and medicine products can be purchased here.');
            }

            $quantityMilli = $this->quantityMilli($data['quantity'], 'quantity');
            $quantity = $quantityMilli / 1000;
            $unitCost = $this->amount($data['unit_cost'], 'unit_cost');
            $total = round($quantity * $unitCost, 2);
            $paid = $this->amount($data['paid_amount'] ?? 0, 'paid_amount');

            if (! is_finite($total) || $total <= 0 || $total > self::MAX_AMOUNT) {
                $this->fail('quantity', 'Purchase total exceeds the supported amount.');
            }

            if ($paid > $total) {
                $this->fail('paid_amount', 'Payment cannot exceed the purchase total.');
            }

            $purchase = SupplyPurchase::query()->create([
                'supplier_id' => $supplier->id,
                'supply_product_id' => $product->id,
                'purchased_on' => $data['purchased_on'],
                'quantity' => $this->quantity($quantityMilli),
                'unit_cost' => $this->money($unitCost),
                'total_amount' => $this->money($total),
                'paid_amount' => $this->money($paid),
                'due_amount' => $this->money($total - $paid),
                'payment_status' => $this->paymentStatus($total, $paid),
                'payment_method' => $paid > 0 ? $data['payment_method'] : null,
                'supplier_invoice' => $data['supplier_invoice'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $lot = SupplyLot::query()->create([
                'supply_product_id' => $product->id,
                'supply_purchase_id' => $purchase->id,
                'received_on' => $data['purchased_on'],
                'expires_on' => $data['expires_on'] ?? null,
                'initial_quantity' => $this->quantity($quantityMilli),
                'remaining_quantity' => $this->quantity($quantityMilli),
                'unit_cost' => $this->money($unitCost),
            ]);

            SupplyStockMovement::query()->create([
                'supply_product_id' => $product->id,
                'supply_lot_id' => $lot->id,
                'movement_type' => 'purchase',
                'quantity_delta' => $this->quantity($quantityMilli),
                'unit_cost' => $this->money($unitCost),
                'total_cost' => $this->money($total),
                'source_type' => SupplyPurchase::class,
                'source_id' => $purchase->id,
                'moved_on' => $data['purchased_on'],
                'notes' => 'Inventory received from purchase '.$purchase->purchase_number,
            ]);

            if ($paid > 0) {
                $purchase->payments()->create([
                    'amount' => $this->money($paid),
                    'paid_on' => $data['purchased_on'],
                    'payment_method' => $data['payment_method'],
                ]);
            }

            return $purchase;
        });
    }

    public function recordPayment(SupplyPurchase $purchase, array $data): SupplyPurchase
    {
        $data = $this->validate($data, [
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', Rule::in(['cash', 'bkash', 'nagad', 'bank'])],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($purchase, $data): SupplyPurchase {
            $lockedPurchase = SupplyPurchase::query()->lockForUpdate()->findOrFail($purchase->id);
            $amount = $this->amount($data['amount'], 'amount');
            $due = (float) $lockedPurchase->due_amount;

            if ($amount <= 0 || $amount > $due) {
                $this->fail('amount', 'Payment must be greater than zero and cannot exceed the supplier balance due.');
            }

            $lockedPurchase->payments()->create([
                'amount' => $this->money($amount),
                'paid_on' => $data['paid_on'],
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $paid = round((float) $lockedPurchase->paid_amount + $amount, 2);
            $total = (float) $lockedPurchase->total_amount;

            $lockedPurchase->update([
                'paid_amount' => $this->money($paid),
                'due_amount' => $this->money($total - $paid),
                'payment_status' => $this->paymentStatus($total, $paid),
            ]);

            return $lockedPurchase;
        });
    }

    public function recordUsage(array $data): SupplyUsage
    {
        $sourceType = $data['source_type'] ?? null;

        $data = $this->validate($data, [
            'supply_product_id' => ['required', 'integer', 'exists:supply_products,id'],
            'source_type' => ['required', Rule::in(['parent_flock', 'chick_batch'])],
            'parent_flock_id' => [Rule::requiredIf($sourceType === 'parent_flock'), 'nullable', 'integer', 'exists:parent_flocks,id'],
            'chick_batch_id' => [Rule::requiredIf($sourceType === 'chick_batch'), 'nullable', 'integer', 'exists:chick_batches,id'],
            'used_on' => ['required', 'date', 'before_or_equal:today'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:1000000.000'],
            'usage_type' => ['nullable', Rule::in(['feed_consumption', 'treatment', 'vaccination'])],
            'diagnosis' => ['nullable', 'string', 'max:150'],
            'dosage' => ['nullable', 'string', 'max:100'],
            'administered_by' => ['nullable', 'string', 'max:100'],
            'next_dose_on' => ['nullable', 'date', 'after:used_on'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($data, $sourceType): SupplyUsage {
            $product = SupplyProduct::query()->lockForUpdate()->findOrFail($data['supply_product_id']);

            if (! $product->is_active || ! in_array($product->type, [SupplyProduct::TYPE_FEED, SupplyProduct::TYPE_MEDICINE], true)) {
                $this->fail('supply_product_id', 'Select an active feed or medicine product.');
            }

            $usageType = $data['usage_type'] ?? ($product->type === SupplyProduct::TYPE_FEED ? 'feed_consumption' : 'treatment');

            if (($product->type === SupplyProduct::TYPE_FEED && $usageType !== 'feed_consumption')
                || ($product->type === SupplyProduct::TYPE_MEDICINE && $usageType === 'feed_consumption')) {
                $this->fail('usage_type', 'Select a usage type that matches the selected inventory product.');
            }

            if ($usageType === 'vaccination' && blank($data['dosage'] ?? null)) {
                $this->fail('dosage', 'Enter the administered vaccine dosage.');
            }

            if ($usageType !== 'vaccination' && filled($data['next_dose_on'] ?? null)) {
                $this->fail('next_dose_on', 'A next-dose reminder can only be recorded for a vaccination.');
            }

            $sourceId = $sourceType === 'parent_flock' ? $data['parent_flock_id'] : $data['chick_batch_id'];

            if ($sourceType === 'parent_flock') {
                $source = ParentFlock::query()->lockForUpdate()->findOrFail($sourceId);

                if ($source->status !== ParentFlock::STATUS_ACTIVE) {
                    $this->fail('parent_flock_id', 'Select an active parent flock.');
                }
            } else {
                $source = ChickBatch::query()->lockForUpdate()->findOrFail($sourceId);

                if ($source->status !== ChickBatch::STATUS_ACTIVE) {
                    $this->fail('chick_batch_id', 'Select an active chick batch.');
                }

                if ($source->current_quantity < 1) {
                    $this->fail('chick_batch_id', 'Production costs can only be assigned to a batch with live birds.');
                }
            }

            if ($sourceType === 'parent_flock' && $source->current_female_quantity < 1) {
                $this->fail('parent_flock_id', 'Flock production costs require at least one live female bird.');
            }

            if ($sourceType === 'parent_flock' && FlockDailyRecord::query()
                ->where('parent_flock_id', $source->id)
                ->whereDate('recorded_on', '>=', $data['used_on'])
                ->exists()) {
                $this->fail('used_on', 'Record flock feed and medicine costs before the daily egg collection on that date.');
            }

            $requestedMilli = $this->quantityMilli($data['quantity'], 'quantity');
            $remainingMilli = $requestedMilli;

            $query = SupplyLot::query()
                ->where('supply_product_id', $product->id)
                ->whereDate('received_on', '<=', $data['used_on'])
                ->where('remaining_quantity', '>', 0);

            if ($product->type === SupplyProduct::TYPE_MEDICINE) {
                $query->whereNotNull('expires_on')
                    ->whereDate('expires_on', '>=', $data['used_on']);
            }

            $lots = $query
                ->orderBy('received_on')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($lots as $lot) {
                if ($remainingMilli <= 0) {
                    break;
                }

                $availableMilli = $this->quantityMilli($lot->remaining_quantity, 'quantity');
                $allocatedMilli = min($remainingMilli, $availableMilli);
                $remainingMilli -= $allocatedMilli;
            }

            if ($remainingMilli > 0) {
                $this->fail('quantity', $product->type === SupplyProduct::TYPE_MEDICINE
                    ? 'Available unexpired medicine lots are not enough for this usage.'
                    : 'Available feed or medicine stock is not enough for this usage.');
            }

            $allocations = [];
            $leftToAllocate = $requestedMilli;
            $totalCost = 0.0;

            foreach ($lots as $lot) {
                if ($leftToAllocate <= 0) {
                    break;
                }

                $availableMilli = $this->quantityMilli($lot->remaining_quantity, 'quantity');
                $allocatedMilli = min($leftToAllocate, $availableMilli);

                if ($allocatedMilli <= 0) {
                    continue;
                }

                $quantity = $allocatedMilli / 1000;
                $unitCost = (float) $lot->unit_cost;
                $lineCost = round($quantity * $unitCost, 2);

                $allocations[] = [$lot, $allocatedMilli, $unitCost, $lineCost];
                $leftToAllocate -= $allocatedMilli;
                $totalCost = round($totalCost + $lineCost, 2);
            }

            $usage = SupplyUsage::query()->create([
                'supply_product_id' => $product->id,
                'parent_flock_id' => $sourceType === 'parent_flock' ? $source->id : null,
                'chick_batch_id' => $sourceType === 'chick_batch' ? $source->id : null,
                'used_on' => $data['used_on'],
                'quantity' => $this->quantity($requestedMilli),
                'total_cost' => $this->money($totalCost),
                'usage_type' => $usageType,
                'diagnosis' => $data['diagnosis'] ?? null,
                'dosage' => $data['dosage'] ?? null,
                'administered_by' => $data['administered_by'] ?? null,
                'next_dose_on' => $data['next_dose_on'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $costService = app(InventoryCostService::class);

            if ($sourceType === 'parent_flock') {
                $costService->addEggProductionCost($source->id, $totalCost, $data['used_on'], $usage);
            } else {
                $costService->addProductionCost(
                    'chick_batch',
                    $source->id,
                    null,
                    $totalCost,
                    $data['used_on'],
                    $usage,
                    'Direct feed or medicine usage capitalized to chick batch production.',
                );
            }

            foreach ($allocations as [$lot, $allocatedMilli, $unitCost, $lineCost]) {
                $lot->decrement('remaining_quantity', $this->quantity($allocatedMilli));

                SupplyUsageLot::query()->create([
                    'supply_usage_id' => $usage->id,
                    'supply_lot_id' => $lot->id,
                    'quantity' => $this->quantity($allocatedMilli),
                    'unit_cost' => $this->money($unitCost),
                    'total_cost' => $this->money($lineCost),
                ]);

                SupplyStockMovement::query()->create([
                    'supply_product_id' => $product->id,
                    'supply_lot_id' => $lot->id,
                    'movement_type' => 'usage',
                    'quantity_delta' => $this->quantity(-$allocatedMilli),
                    'unit_cost' => $this->money($unitCost),
                    'total_cost' => $this->money($lineCost),
                    'source_type' => SupplyUsage::class,
                    'source_id' => $usage->id,
                    'moved_on' => $data['used_on'],
                    'notes' => 'Inventory usage '.$usage->usage_number,
                ]);
            }

            return $usage;
        });
    }

    private function quantityMilli(mixed $value, string $field): int
    {
        if (! is_numeric($value) || ! is_finite((float) $value) || (float) $value <= 0 || (float) $value > 1000000.000) {
            $this->fail($field, 'Enter a positive quantity within the supported range.');
        }

        $scaled = (float) $value * 1000;

        if (abs($scaled - round($scaled)) > 0.00001) {
            $this->fail($field, 'Quantity may have no more than three decimal places.');
        }

        return (int) round($scaled);
    }

    private function quantity(int $milli): string
    {
        return number_format($milli / 1000, 3, '.', '');
    }

    private function amount(mixed $value, string $field): float
    {
        if (! is_numeric($value) || ! is_finite((float) $value) || abs((float) $value) > self::MAX_AMOUNT) {
            $this->fail($field, 'Enter a valid monetary amount within the supported range.');
        }

        return round((float) $value, 2);
    }

    private function paymentStatus(float $total, float $paid): string
    {
        if ($paid >= $total) {
            return SupplyPurchase::PAYMENT_PAID;
        }

        return $paid > 0 ? SupplyPurchase::PAYMENT_PARTIAL : SupplyPurchase::PAYMENT_DUE;
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    private function validate(array $data, array $rules): array
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

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages(['data.'.$field => $message]);
    }
}

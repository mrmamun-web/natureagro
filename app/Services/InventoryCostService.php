<?php

namespace App\Services;

use App\Models\InventoryCostLayer;
use App\Models\InventoryCostMovement;
use App\Models\ChickBatch;
use App\Models\EggStock;
use App\Models\IncubationBatch;
use App\Models\ParentFlock;
use App\Models\Poultry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryCostService
{
    public function receive(
        string $type,
        int $id,
        ?string $gender,
        float $quantity,
        ?float $totalCost,
        string $date,
        string $layerType,
        ?Model $source = null,
        ?string $notes = null,
        ?string $movementDate = null,
        ?float $productionUnits = null,
    ): InventoryCostLayer {
        $quantity = round($quantity, 3);
        $totalCost = $totalCost === null ? null : round($totalCost, 2);
        if ($type === 'parent_flock' && $gender === 'female') {
            $productionUnits ??= $quantity * $this->lifetimeGoodEggsPerHen();
        }
        $productionUnits = $productionUnits === null ? null : round($productionUnits, 3);

        if ($quantity <= 0 || ($totalCost !== null && (! is_finite($totalCost) || $totalCost < 0 || $totalCost > 999999999999.99))) {
            throw ValidationException::withMessages([
                'data.quantity' => 'Inventory receipts must have a positive quantity and a supported non-negative cost.',
            ]);
        }
        if ($productionUnits !== null && (! is_finite($productionUnits) || $productionUnits < 0 || $productionUnits > 9999999999999.999)) {
            throw ValidationException::withMessages([
                'data.quantity' => 'Inventory production units exceed the supported range.',
            ]);
        }

        $layer = InventoryCostLayer::query()->create([
            'inventory_type' => $type,
            'inventory_id' => $id,
            'gender' => $gender,
            'layer_type' => $layerType,
            'acquired_on' => $date,
            'quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'total_cost' => $totalCost,
            'remaining_cost' => $totalCost,
            'total_production_units' => $productionUnits,
            'remaining_production_units' => $productionUnits,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
        ]);

        $this->recordMovement(
            $layer,
            $type,
            $id,
            $gender,
            $layerType,
            $quantity,
            $totalCost,
            $movementDate ?? $date,
            $source,
            $notes,
            $productionUnits ?? 0,
        );

        return $layer;
    }

    /**
     * @return array{total_cost: float, allocations: array<int, array{quantity: float, cost: float, acquired_on: string, production_units: ?float}>}
     */
    public function consume(
        string $type,
        int $id,
        ?string $gender,
        float $quantity,
        string $date,
        string $movementType,
        ?Model $source = null,
        ?string $notes = null,
    ): array {
        $quantity = round($quantity, 3);
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'data.quantity' => 'Inventory quantity must be greater than zero.',
            ]);
        }

        if ($this->hasFutureStockMovement($type, $id, $gender, $date)) {
            throw ValidationException::withMessages([
                'data.moved_on' => 'Costed stock changes must be recorded in date order. Correct the later stock movement first.',
            ]);
        }

        $layers = $this->stockLayers($type, $id, $gender)
            ->whereDate('acquired_on', '<=', $date)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('acquired_on')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $available = round((float) $layers->sum('remaining_quantity'), 3);

        if ($available + 0.0001 < $quantity) {
            throw ValidationException::withMessages([
                'data.quantity' => 'Costed inventory does not cover this stock change. Enter the opening stock valuation first.',
            ]);
        }

        $left = $quantity;
        $totalCost = 0.0;
        $allocations = [];

        foreach ($layers as $layer) {
            if ($left <= 0) {
                break;
            }

            if ($layer->remaining_cost === null) {
                throw ValidationException::withMessages([
                    'data.quantity' => 'This opening stock has no valuation. Enter its cost before selling, transferring, or recording mortality.',
                ]);
            }

            $layerQuantity = (float) $layer->remaining_quantity;
            $allocated = min($left, $layerQuantity);
            $lineCost = $allocated >= $layerQuantity
                ? (float) $layer->remaining_cost
                : round((float) $layer->remaining_cost * $allocated / $layerQuantity, 2);
            $allocatedProductionUnits = null;
            if ($layer->remaining_production_units !== null) {
                $remainingProductionUnits = (float) $layer->remaining_production_units;
                $allocatedProductionUnits = $allocated >= $layerQuantity
                    ? $remainingProductionUnits
                    : round($remainingProductionUnits * $allocated / $layerQuantity, 3);
                $layer->remaining_production_units = number_format($remainingProductionUnits - $allocatedProductionUnits, 3, '.', '');
            }
            $layer->remaining_quantity = number_format($layerQuantity - $allocated, 3, '.', '');
            $layer->remaining_cost = number_format((float) $layer->remaining_cost - $lineCost, 2, '.', '');
            $layer->save();

            $this->recordMovement(
                $layer,
                $type,
                $id,
                $gender,
                $movementType,
                -$allocated,
                -$lineCost,
                $date,
                $source,
                $notes,
                -($allocatedProductionUnits ?? 0),
            );

            $allocations[] = [
                'quantity' => $allocated,
                'cost' => $lineCost,
                'acquired_on' => $layer->acquired_on->toDateString(),
                'production_units' => $allocatedProductionUnits,
            ];
            $left = round($left - $allocated, 3);
            $totalCost = round($totalCost + $lineCost, 2);
        }

        return ['total_cost' => $totalCost, 'allocations' => $allocations];
    }

    /**
     * @param  array<int, array{quantity: float, cost: float, acquired_on: string, production_units: ?float}>  $allocations
     */
    public function receiveTransfer(
        string $type,
        int $id,
        ?string $gender,
        array $allocations,
        string $date,
        ?Model $source = null,
        ?string $notes = null,
    ): float {
        $totalCost = 0.0;

        foreach ($allocations as $allocation) {
            $this->receive(
                $type,
                $id,
                $gender,
                $allocation['quantity'],
                $allocation['cost'],
                $allocation['acquired_on'],
                'transfer_in',
                $source,
                $notes,
                $date,
                $allocation['production_units'],
            );
            $totalCost = round($totalCost + $allocation['cost'], 2);
        }

        return $totalCost;
    }

    public function addProductionCost(
        string $type,
        int $id,
        ?string $gender,
        float $amount,
        string $date,
        Model $source,
        string $notes,
    ): void {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return;
        }

        if (! is_finite($amount) || $amount > 999999999999.99) {
            throw ValidationException::withMessages([
                'data.used_on' => 'The production cost exceeds the supported amount.',
            ]);
        }

        if ($this->hasFutureStockMovement($type, $id, $gender, $date)
            || $this->hasStockReductionOnOrAfter($type, $id, $gender, $date)) {
            throw ValidationException::withMessages([
                'data.used_on' => 'Production costs must be recorded before sales, mortality, or transfers for the same stock dates.',
            ]);
        }

        $layers = $this->stockLayers($type, $id, $gender)
            ->whereDate('acquired_on', '<=', $date)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('acquired_on')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $quantity = (float) $layers->sum('remaining_quantity');
        if ($quantity <= 0 || $layers->contains(fn (InventoryCostLayer $layer): bool => $layer->remaining_cost === null)) {
            throw ValidationException::withMessages([
                'data.supply_product_id' => 'Value the live stock before capitalizing feed or medicine costs.',
            ]);
        }

        $remainingAmount = $amount;
        $remainingQuantity = $quantity;
        foreach ($layers as $index => $layer) {
            $isLast = $index === $layers->count() - 1;
            $share = $isLast
                ? $remainingAmount
                : round($remainingAmount * (float) $layer->remaining_quantity / $remainingQuantity, 2);
            $layer->remaining_cost = number_format((float) $layer->remaining_cost + $share, 2, '.', '');
            $layer->save();

            $this->recordMovement(
                $layer,
                $type,
                $id,
                $gender,
                'production_cost',
                0,
                $share,
                $date,
                $source,
                $notes,
            );
            $remainingAmount = round($remainingAmount - $share, 2);
            $remainingQuantity -= (float) $layer->remaining_quantity;
        }
    }

    public function addEggProductionCost(int $parentFlockId, float $amount, string $date, Model $source): void
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return;
        }

        $this->recordMovement(
            null,
            'egg_pool',
            $parentFlockId,
            null,
            'production_cost',
            0,
            $amount,
            $date,
            $source,
            'Direct feed or medicine cost assigned to this flock’s future good eggs.',
        );
    }

    public function allocateBreederCostToEggs(int $parentFlockId, int $goodEggs, string $date, Model $source): float
    {
        if ($goodEggs <= 0) {
            return 0.0;
        }

        if ($this->hasFutureStockMovement('parent_flock', $parentFlockId, 'male', $date)
            || $this->hasFutureStockMovement('parent_flock', $parentFlockId, 'female', $date)
            || $this->hasStockReductionOnOrAfter('parent_flock', $parentFlockId, 'male', $date, $source)
            || $this->hasStockReductionOnOrAfter('parent_flock', $parentFlockId, 'female', $date, $source)) {
            throw ValidationException::withMessages([
                'data.recorded_on' => 'Record flock production before later sales or transfers so breeder cost is allocated to the correct eggs.',
            ]);
        }

        $untrackedFemaleStock = $this->stockLayers('parent_flock', $parentFlockId, 'female')
            ->where('remaining_quantity', '>', 0)
            ->whereNull('remaining_production_units')
            ->exists();
        if ($untrackedFemaleStock) {
            throw ValidationException::withMessages([
                'data.parent_flock_id' => 'Set expected egg production units for the live female breeder stock before recording egg collection.',
            ]);
        }

        $femaleLayers = $this->stockLayers('parent_flock', $parentFlockId, 'female')
            ->where('remaining_quantity', '>', 0)
            ->orderBy('acquired_on')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $remainingEggUnits = round((float) $femaleLayers->sum('remaining_production_units'), 3);
        if ($remainingEggUnits <= 0) {
            return 0.0;
        }
        $unitsToAllocate = min((float) $goodEggs, $remainingEggUnits);

        $maleLayers = $this->stockLayers('parent_flock', $parentFlockId, 'male')
            ->where('remaining_quantity', '>', 0)
            ->orderBy('acquired_on')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $allLayers = $femaleLayers->concat($maleLayers);
        if ($allLayers->contains(fn (InventoryCostLayer $layer): bool => $layer->remaining_cost === null)) {
            throw ValidationException::withMessages([
                'data.parent_flock_id' => 'Value the live breeder flock before allocating its purchase cost to eggs.',
            ]);
        }

        $maleCost = round((float) $maleLayers->sum('remaining_cost'), 2);
        $maleCostToAllocate = $unitsToAllocate >= $remainingEggUnits
            ? $maleCost
            : ($maleCost > 0 ? round($maleCost * $unitsToAllocate / $remainingEggUnits, 2) : 0.0);
        $remainingMaleCostToAllocate = $maleCostToAllocate;
        foreach ($maleLayers as $index => $layer) {
            $layerCost = (float) $layer->remaining_cost;
            $share = $index === $maleLayers->count() - 1
                ? $remainingMaleCostToAllocate
                : ($maleCost > 0 ? round($maleCostToAllocate * $layerCost / $maleCost, 2) : 0.0);
            $share = min($share, $layerCost);
            $layer->remaining_cost = number_format($layerCost - $share, 2, '.', '');
            $layer->save();
            if ($share > 0) {
                $this->recordMovement(
                    $layer,
                    'parent_flock',
                    $parentFlockId,
                    'male',
                    'egg_production_cost',
                    0,
                    -$share,
                    $date,
                    $source,
                    'Male breeder acquisition cost allocated across expected good egg production.',
                );
            }
            $remainingMaleCostToAllocate = round($remainingMaleCostToAllocate - $share, 2);
        }

        $femaleCostToAllocate = 0.0;
        $remainingUnitsToAllocate = $unitsToAllocate;
        foreach ($femaleLayers as $layer) {
            if ($remainingUnitsToAllocate <= 0) {
                break;
            }

            $layerUnits = (float) $layer->remaining_production_units;
            if ($layerUnits <= 0) {
                continue;
            }
            $allocatedUnits = min($remainingUnitsToAllocate, $layerUnits);
            $layerCost = (float) $layer->remaining_cost;
            $share = $allocatedUnits >= $layerUnits
                ? $layerCost
                : ($layerUnits > 0 && $layerCost > 0 ? round($layerCost * $allocatedUnits / $layerUnits, 2) : 0.0);
            $layer->remaining_production_units = number_format($layerUnits - $allocatedUnits, 3, '.', '');
            $layer->remaining_cost = number_format($layerCost - $share, 2, '.', '');
            $layer->save();
            $this->recordMovement(
                $layer,
                'parent_flock',
                $parentFlockId,
                'female',
                'egg_production_cost',
                0,
                -$share,
                $date,
                $source,
                'Female breeder acquisition cost allocated across expected good egg production.',
                -$allocatedUnits,
            );
            $femaleCostToAllocate = round($femaleCostToAllocate + $share, 2);
            $remainingUnitsToAllocate = round($remainingUnitsToAllocate - $allocatedUnits, 3);
        }

        $costToAllocate = round($femaleCostToAllocate + $maleCostToAllocate, 2);

        if ($costToAllocate > 0) {
            $this->recordMovement(
                null,
                'egg_pool',
                $parentFlockId,
                null,
                'production_cost',
                0,
                $costToAllocate,
                $date,
                $source,
                'Units-of-production allocation of breeder flock acquisition cost to good eggs.',
            );
        }

        return $costToAllocate;
    }

    public function consumeEggProductionCost(int $parentFlockId, float $amount, string $date, Model $source): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->recordMovement(
            null,
            'egg_pool',
            $parentFlockId,
            null,
            'cost_transfer_out',
            0,
            -round($amount, 2),
            $date,
            $source,
            'Production cost transferred to collected good eggs.',
        );
    }

    public function valueOpeningStock(
        string $type,
        int $id,
        ?string $gender,
        float $unitCost,
        string $date,
        ?float $productionUnitsPerUnit = null,
    ): float {
        if (! is_finite($unitCost) || $unitCost < 0 || $unitCost > 9999999999.99) {
            throw ValidationException::withMessages([
                'data.unit_cost' => 'Enter a valid unit cost within the supported range.',
            ]);
        }
        if ($type === 'parent_flock' && $gender === 'female') {
            $productionUnitsPerUnit ??= $this->lifetimeGoodEggsPerHen();
        }
        if ($productionUnitsPerUnit !== null
            && (! is_finite($productionUnitsPerUnit) || $productionUnitsPerUnit < 1 || $productionUnitsPerUnit > 1000000)) {
            throw ValidationException::withMessages([
                'data.production_units_per_unit' => 'Enter a positive expected egg count per female bird.',
            ]);
        }

        return DB::transaction(function () use ($type, $id, $gender, $unitCost, $date, $productionUnitsPerUnit): float {
            $currentQuantity = $this->lockCurrentQuantity($type, $id, $gender);
            $layers = $this->stockLayers($type, $id, $gender)
                ->where('remaining_quantity', '>', 0)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $unvalued = $layers->filter(fn (InventoryCostLayer $layer): bool => $layer->remaining_cost === null);
            $quantity = round((float) $unvalued->sum('remaining_quantity'), 3);

            if ($quantity <= 0 || abs($quantity - round($currentQuantity, 3)) > 0.0001) {
                throw ValidationException::withMessages([
                    'data.unit_cost' => 'Stock changed or has already been valued. Refresh the page and review the current quantity.',
                ]);
            }

            foreach ($unvalued as $layer) {
                $this->recordMovement(
                    $layer,
                    $type,
                    $id,
                    $gender,
                    'opening_valuation',
                    -(float) $layer->remaining_quantity,
                    null,
                    $date,
                    null,
                    'Unvalued opening quantity reclassified into a valued FIFO layer.',
                    -(float) ($layer->remaining_production_units ?? 0),
                );
                $layer->remaining_quantity = 0;
                $layer->remaining_production_units = $layer->remaining_production_units === null
                    ? null
                    : '0.000';
                $layer->save();
            }

            $totalCost = round($quantity * $unitCost, 2);
            if (! is_finite($totalCost) || $totalCost > 999999999999.99) {
                throw ValidationException::withMessages([
                    'data.unit_cost' => 'The total opening valuation exceeds the supported amount.',
                ]);
            }
            $productionUnits = $productionUnitsPerUnit === null
                ? null
                : round($quantity * $productionUnitsPerUnit, 3);
            if ($productionUnits !== null && $productionUnits > 9999999999999.999) {
                throw ValidationException::withMessages([
                    'data.production_units_per_unit' => 'The total expected egg production exceeds the supported range.',
                ]);
            }
            $this->receive(
                $type,
                $id,
                $gender,
                $quantity,
                $totalCost,
                $date,
                'opening_valuation',
                null,
                'One-time opening valuation entered by the farm administrator.',
                null,
                $productionUnits,
            );

            return $totalCost;
        });
    }

    public function needsOpeningValuation(string $type, int $id, ?string $gender = null): bool
    {
        return $this->stockLayers($type, $id, $gender)
            ->where('remaining_quantity', '>', 0)
            ->whereNull('remaining_cost')
            ->exists();
    }

    public function currentCost(string $type, int $id, ?string $gender = null): float
    {
        return (float) $this->stockLayers($type, $id, $gender)
            ->where('remaining_quantity', '>', 0)
            ->sum('remaining_cost');
    }

    private function lifetimeGoodEggsPerHen(): int
    {
        $units = DB::table('inventory_costing_settings')->where('id', 1)->value('lifetime_good_eggs_per_hen');

        if (! is_numeric($units) || (int) $units < 1) {
            throw ValidationException::withMessages([
                'data.quantity' => 'Configure a valid lifetime egg-production estimate before adding female breeder stock.',
            ]);
        }

        return (int) $units;
    }

    private function stockLayers(string $type, int $id, ?string $gender)
    {
        return InventoryCostLayer::query()
            ->where('inventory_type', $type)
            ->where('inventory_id', $id)
            ->where('gender', $gender);
    }

    private function hasFutureStockMovement(string $type, int $id, ?string $gender, string $date): bool
    {
        return InventoryCostMovement::query()
            ->where('inventory_type', $type)
            ->where('inventory_id', $id)
            ->where('gender', $gender)
            ->whereDate('moved_on', '>', $date)
            ->exists();
    }

    private function hasStockReductionOnOrAfter(string $type, int $id, ?string $gender, string $date, ?Model $excludeSource = null): bool
    {
        $query = InventoryCostMovement::query()
            ->where('inventory_type', $type)
            ->where('inventory_id', $id)
            ->where('gender', $gender)
            ->whereDate('moved_on', '>=', $date)
            ->whereIn('movement_type', ['sale', 'mortality', 'transfer_out']);

        if ($excludeSource !== null) {
            $query->where(function ($query) use ($excludeSource): void {
                $query->whereNull('source_type')
                    ->orWhereNull('source_id')
                    ->orWhere('source_type', '!=', $excludeSource->getMorphClass())
                    ->orWhere('source_id', '!=', $excludeSource->getKey());
            });
        }

        return $query->exists();
    }

    private function lockCurrentQuantity(string $type, int $id, ?string $gender): float
    {
        return match ($type) {
            'parent_flock' => $this->lockParentFlockQuantity($id, $gender),
            'poultry' => (float) Poultry::query()->lockForUpdate()->findOrFail($id)->current_quantity,
            'chick_batch' => (float) ChickBatch::query()->lockForUpdate()->findOrFail($id)->current_quantity,
            'egg_stock' => (float) EggStock::query()->lockForUpdate()->findOrFail($id)->good_available,
            'incubation_batch' => $this->lockIncubationQuantity($id),
            default => throw ValidationException::withMessages([
                'data.unit_cost' => 'This inventory type cannot receive an opening valuation.',
            ]),
        };
    }

    private function lockParentFlockQuantity(int $id, ?string $gender): float
    {
        $flock = ParentFlock::query()->lockForUpdate()->findOrFail($id);

        return match ($gender) {
            'male' => (float) $flock->current_male_quantity,
            'female' => (float) $flock->current_female_quantity,
            default => throw ValidationException::withMessages([
                'data.unit_cost' => 'Select the male or female parent flock inventory.',
            ]),
        };
    }

    private function lockIncubationQuantity(int $id): float
    {
        $batch = IncubationBatch::query()->lockForUpdate()->findOrFail($id);

        if ($batch->status !== IncubationBatch::STATUS_INCUBATING) {
            throw ValidationException::withMessages([
                'data.unit_cost' => 'Only eggs currently reserved for incubation can be valued.',
            ]);
        }

        return (float) $batch->eggs_set;
    }

    private function recordMovement(
        ?InventoryCostLayer $layer,
        string $type,
        int $id,
        ?string $gender,
        string $movementType,
        float $quantityDelta,
        ?float $costDelta,
        string $date,
        ?Model $source,
        ?string $notes,
        float $productionUnitsDelta = 0,
    ): InventoryCostMovement {
        return InventoryCostMovement::query()->create([
            'inventory_cost_layer_id' => $layer?->id,
            'inventory_type' => $type,
            'inventory_id' => $id,
            'gender' => $gender,
            'movement_type' => $movementType,
            'quantity_delta' => number_format($quantityDelta, 3, '.', ''),
            'cost_delta' => $costDelta === null ? null : number_format($costDelta, 2, '.', ''),
            'production_units_delta' => number_format($productionUnitsDelta, 3, '.', ''),
            'moved_on' => $date,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'notes' => $notes,
        ]);
    }
}

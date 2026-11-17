<?php

namespace App\Services;

use App\Exceptions\RecordHasDependencies;
use App\Models\InventoryCostLayer;
use Illuminate\Support\Collection;

class InventoryCostLayerRecalculator
{
    /**
     * @param  Collection<int, int|string>  $layerIds
     */
    public function recalculate(Collection $layerIds): void
    {
        foreach (InventoryCostLayer::query()->whereKey($layerIds)->lockForUpdate()->get() as $layer) {
            $movements = $layer->movements()->get();
            $remainingQuantity = round((float) $movements->sum('quantity_delta'), 3);
            $remainingCost = $layer->total_cost === null
                ? null
                : round((float) $movements->sum('cost_delta'), 2);
            $remainingProductionUnits = $layer->total_production_units === null
                ? null
                : round((float) $movements->sum('production_units_delta'), 3);

            if ($remainingQuantity < 0
                || ($remainingCost !== null && $remainingCost < 0)
                || ($remainingProductionUnits !== null && $remainingProductionUnits < 0)) {
                throw new RecordHasDependencies(
                    'The remaining stock or cost layer cannot be recalculated safely. Nothing was removed.',
                );
            }

            $layer->remaining_quantity = number_format($remainingQuantity, 3, '.', '');
            $layer->remaining_cost = $remainingCost === null ? null : number_format($remainingCost, 2, '.', '');
            $layer->remaining_production_units = $remainingProductionUnits === null
                ? null
                : number_format($remainingProductionUnits, 3, '.', '');
            $layer->save();
        }
    }
}

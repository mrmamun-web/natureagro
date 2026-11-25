<?php

namespace App\Services;

use App\Exceptions\RecordHasDependencies;
use App\Models\ChickBatch;
use App\Models\ChickBatchMovement;
use App\Models\Customer;
use App\Models\EggInventoryMovement;
use App\Models\EggStock;
use App\Models\FarmTransaction;
use App\Models\FlockDailyRecord;
use App\Models\IncubationBatch;
use App\Models\InventoryCostMovement;
use App\Models\ParentFlock;
use App\Models\ParentFlockMovement;
use App\Models\Poultry;
use App\Models\PoultryMovement;
use App\Models\Sale;
use App\Models\SupplyLot;
use App\Models\SupplyProduct;
use App\Models\SupplyPurchase;
use App\Models\SupplyUsage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SafeRecordDeletionService
{
    /**
     * @var array<string, array<string, list<array{table: string, column: string}>>>
     */
    private static array $foreignKeyDependents = [];

    /**
     * @var array<string, array<string, array<string, int|float>>>
     */
    private array $stockAdjustments = [];

    /**
     * @var array<int, int|string>
     */
    private array $affectedCostLayerIds = [];

    /**
     * @var array<string, true>
     */
    private array $visited = [];

    public function delete(Model $record): void
    {
        $this->stockAdjustments = [];
        $this->affectedCostLayerIds = [];
        $this->visited = [];

        try {
            DB::transaction(function () use ($record): void {
                $lockedRecord = $record->newQuery()
                    ->lockForUpdate()
                    ->findOrFail($record->getKey());

                if ($lockedRecord instanceof ParentFlock) {
                    app(ParentFlockDeletionService::class)
                        ->deleteWithinTransaction($lockedRecord);

                    return;
                }

                if ($lockedRecord instanceof FlockDailyRecord) {
                    $this->deleteLaterEggDependents($lockedRecord);
                }

                $this->deleteRow(
                    $lockedRecord->getTable(),
                    $lockedRecord->getKey(),
                    $lockedRecord,
                );

                $this->applyStockAdjustments();
                app(InventoryCostLayerRecalculator::class)->recalculate(
                    collect($this->affectedCostLayerIds)->unique()->values(),
                );
            });
        } catch (QueryException $exception) {
            if (! $this->isForeignKeyViolation($exception)) {
                throw $exception;
            }

            throw new RecordHasDependencies(
                'The linked records could not all be removed. Nothing was deleted; please try again or contact support.',
                previous: $exception,
            );
        }
    }

    private function deleteRow(string $table, int|string $id, ?Model $model = null): void
    {
        $key = $table.':'.$id;

        if (isset($this->visited[$key]) || ! Schema::hasColumn($table, 'id')) {
            return;
        }

        $this->visited[$key] = true;

        $row = DB::table($table)->where('id', $id)->lockForUpdate()->first();

        if ($row === null) {
            return;
        }

        $model ??= $this->modelForTable($table);

        if ($table === 'inventory_cost_layers') {
            $this->deleteCostMovementSources($id);
        }

        if ($table === 'supply_usage_lots' && isset($row->supply_usage_id)) {
            $this->deleteRow('supply_usages', $row->supply_usage_id);
        }

        if (in_array($table, ['inventory_cost_movements', 'egg_inventory_movements', 'supply_stock_movements'], true)) {
            $this->deleteMorphSource($row->source_type ?? null, $row->source_id ?? null);
        }

        $this->deletePolymorphicDependents($table, $id, $model);
        $this->deleteInventoryCostRecords($table, $id);

        foreach ($this->foreignKeyDependents()[$table] ?? [] as $dependent) {
            $childIds = DB::table($dependent['table'])
                ->where($dependent['column'], $id)
                ->lockForUpdate()
                ->pluck('id');

            foreach ($childIds as $childId) {
                $this->deleteRow($dependent['table'], $childId);
            }
        }

        $this->recordStockAdjustment($table, $row);

        DB::table($table)->where('id', $id)->delete();
    }

    private function deleteLaterEggDependents(FlockDailyRecord $record): void
    {
        $sourceMovementIds = EggInventoryMovement::query()
            ->whereIn('source_type', [
                $record->getMorphClass(),
                FlockDailyRecord::class,
                'legacy_flock_daily_record',
            ])
            ->where('source_id', $record->id)
            ->pluck('id');

        if ($sourceMovementIds->isEmpty()) {
            return;
        }

        $laterMovements = EggInventoryMovement::query()
            ->where('parent_flock_id', $record->parent_flock_id)
            ->where('id', '>', (int) $sourceMovementIds->max())
            ->whereIn('movement_type', [
                EggInventoryMovement::TYPE_INCUBATION_RESERVE,
                EggInventoryMovement::TYPE_HATCH_OUTCOME,
                'sale',
            ])
            ->get(['source_type', 'source_id']);

        foreach ($laterMovements as $movement) {
            $this->deleteMorphSource($movement->source_type, $movement->source_id);
        }
    }

    private function deleteCostMovementSources(int|string $layerId): void
    {
        $movements = InventoryCostMovement::query()
            ->where('inventory_cost_layer_id', $layerId)
            ->get(['source_type', 'source_id']);

        foreach ($movements as $movement) {
            $this->deleteMorphSource($movement->source_type, $movement->source_id);
        }
    }

    private function deletePolymorphicDependents(string $table, int|string $id, ?Model $model): void
    {
        $sourceTypes = $this->morphTypesFor($table, $model);

        if ($sourceTypes === []) {
            return;
        }

        foreach ([
            'egg_inventory_movements',
            'inventory_cost_layers',
            'inventory_cost_movements',
            'supply_stock_movements',
        ] as $dependentTable) {
            if (! Schema::hasTable($dependentTable)
                || ! Schema::hasColumns($dependentTable, ['source_type', 'source_id'])) {
                continue;
            }

            $dependentIds = DB::table($dependentTable)
                ->whereIn('source_type', $sourceTypes)
                ->where('source_id', $id)
                ->lockForUpdate()
                ->pluck('id');

            foreach ($dependentIds as $dependentId) {
                $this->deleteRow($dependentTable, $dependentId);
            }
        }
    }

    private function deleteInventoryCostRecords(string $table, int|string $id): void
    {
        $inventoryTypes = match ($table) {
            'parent_flocks' => ['parent_flock', 'egg_pool'],
            'egg_stocks' => ['egg_stock'],
            'incubation_batches' => ['incubation_batch'],
            'chick_batches' => ['chick_batch'],
            'poultries' => ['poultry'],
            default => [],
        };

        foreach ($inventoryTypes as $inventoryType) {
            foreach (['inventory_cost_layers', 'inventory_cost_movements'] as $dependentTable) {
                $dependentIds = DB::table($dependentTable)
                    ->where('inventory_type', $inventoryType)
                    ->where('inventory_id', $id)
                    ->lockForUpdate()
                    ->pluck('id');

                foreach ($dependentIds as $dependentId) {
                    $this->deleteRow($dependentTable, $dependentId);
                }
            }
        }
    }

    private function deleteMorphSource(?string $sourceType, int|string|null $sourceId): void
    {
        if (blank($sourceType) || $sourceId === null) {
            return;
        }

        $sourceType = $sourceType === 'legacy_flock_daily_record'
            ? FlockDailyRecord::class
            : (Relation::getMorphedModel($sourceType) ?? $sourceType);

        if (! class_exists($sourceType) || ! is_subclass_of($sourceType, Model::class)) {
            return;
        }

        /** @var Model $sourceModel */
        $sourceModel = new $sourceType;
        $this->deleteRow($sourceModel->getTable(), $sourceId, $sourceModel);
    }

    private function recordStockAdjustment(string $table, object $row): void
    {
        if ($table === 'egg_inventory_movements') {
            $this->addAdjustment('egg_stocks', (string) $row->parent_flock_id, [
                'good_available' => -(int) $row->good_delta,
                'damaged' => -(int) $row->damaged_delta,
                'reserved_for_hatching' => -(int) $row->reserved_delta,
            ]);
        }

        if ($table === 'parent_flock_movements') {
            $sign = in_array($row->movement_type, ['opening_stock', 'transfer_in'], true) ? 1
                : (in_array($row->movement_type, ['mortality', 'sale', 'transfer_out'], true) ? -1 : 0);

            $this->addAdjustment('parent_flocks', (string) $row->parent_flock_id, [
                'current_male_quantity' => -$sign * (int) $row->male_quantity,
                'current_female_quantity' => -$sign * (int) $row->female_quantity,
            ]);
        }

        if ($table === 'poultry_movements') {
            $sign = in_array($row->movement_type, ['opening_stock', 'transfer_in'], true) ? 1
                : (in_array($row->movement_type, ['mortality', 'sale', 'transfer_out'], true) ? -1 : 0);

            $this->addAdjustment('poultries', (string) $row->poultry_id, [
                'current_quantity' => -$sign * (int) $row->quantity,
            ]);
        }

        if ($table === 'chick_batch_movements') {
            $sign = $row->movement_type === ChickBatchMovement::TYPE_TRANSFER_RECEIVED ? 1
                : (in_array($row->movement_type, [
                    ChickBatchMovement::TYPE_MORTALITY,
                    ChickBatchMovement::TYPE_TRANSFER,
                    ChickBatchMovement::TYPE_SALE,
                ], true) ? -1 : 0);

            $this->addAdjustment('chick_batches', (string) $row->chick_batch_id, [
                'current_quantity' => -$sign * (int) $row->quantity,
            ]);
        }

        if ($table === 'supply_stock_movements' && $row->supply_lot_id !== null) {
            $this->addAdjustment('supply_lots', (string) $row->supply_lot_id, [
                'remaining_quantity' => -(float) $row->quantity_delta,
            ]);
        }

        if ($table === 'inventory_cost_movements' && $row->inventory_cost_layer_id !== null) {
            $this->affectedCostLayerIds[] = $row->inventory_cost_layer_id;
        }
    }

    /**
     * @param  array<string, int|float>  $values
     */
    private function addAdjustment(string $table, string $id, array $values): void
    {
        foreach ($values as $column => $amount) {
            $this->stockAdjustments[$table][$id][$column] =
                ($this->stockAdjustments[$table][$id][$column] ?? 0) + $amount;
        }
    }

    private function applyStockAdjustments(): void
    {
        foreach ($this->stockAdjustments as $table => $records) {
            foreach ($records as $id => $columns) {
                $keyColumn = $table === 'egg_stocks' ? 'parent_flock_id' : 'id';
                $record = DB::table($table)->where($keyColumn, $id)->lockForUpdate()->first();

                if ($record === null) {
                    continue;
                }

                $updates = [];

                foreach ($columns as $column => $amount) {
                    $current = (float) $record->{$column};
                    $next = $current + $amount;

                    if ($next < -0.0001) {
                        throw new RecordHasDependencies(
                            'The cascade would make linked inventory negative. Nothing was deleted.',
                        );
                    }

                    $updates[$column] = in_array($column, [
                        'current_male_quantity',
                        'current_female_quantity',
                        'current_quantity',
                        'good_available',
                        'damaged',
                        'reserved_for_hatching',
                    ], true)
                        ? max(0, (int) round($next))
                        : number_format(max(0, $next), 3, '.', '');
                }

                DB::table($table)->where($keyColumn, $id)->update($updates);

                if ($table === 'poultries' && ($updates['current_quantity'] ?? null) > 0) {
                    DB::table($table)->where('id', $id)->update(['status' => Poultry::STATUS_ACTIVE]);
                }

                if ($table === 'chick_batches' && ($updates['current_quantity'] ?? null) > 0) {
                    DB::table($table)->where('id', $id)->update(['status' => ChickBatch::STATUS_ACTIVE]);
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private function morphTypesFor(string $table, ?Model $model): array
    {
        $modelClass = $model?->getMorphClass();

        if ($modelClass === null) {
            $modelClass = match ($table) {
                'parent_flocks' => ParentFlock::class,
                'flock_daily_records' => FlockDailyRecord::class,
                'incubation_batches' => IncubationBatch::class,
                'chick_batches' => ChickBatch::class,
                'poultries' => Poultry::class,
                'sales' => Sale::class,
                'supply_purchases' => SupplyPurchase::class,
                'supply_usages' => SupplyUsage::class,
                default => null,
            };
        }

        if ($modelClass === null) {
            return [];
        }

        $types = [$modelClass];

        if ($model !== null) {
            $types[] = $model::class;
        }

        if ($table === 'flock_daily_records') {
            $types[] = 'legacy_flock_daily_record';
        }

        return array_values(array_unique($types));
    }

    private function modelForTable(string $table): ?Model
    {
        $modelClass = match ($table) {
            'parent_flocks' => ParentFlock::class,
            'flock_daily_records' => FlockDailyRecord::class,
            'incubation_batches' => IncubationBatch::class,
            'chick_batches' => ChickBatch::class,
            'poultries' => Poultry::class,
            'sales' => Sale::class,
            'customers' => Customer::class,
            'farm_transactions' => FarmTransaction::class,
            'supply_products' => SupplyProduct::class,
            'supply_purchases' => SupplyPurchase::class,
            'supply_usages' => SupplyUsage::class,
            'supply_lots' => SupplyLot::class,
            'egg_stocks' => EggStock::class,
            default => null,
        };

        return $modelClass === null ? null : new $modelClass;
    }

    /**
     * @return array<string, list<array{table: string, column: string}>>
     */
    private function foreignKeyDependents(): array
    {
        $connection = DB::connection();
        $cacheKey = $connection->getName().'|'.$connection->getDatabaseName();

        if (isset(self::$foreignKeyDependents[$cacheKey])) {
            return self::$foreignKeyDependents[$cacheKey];
        }

        $dependents = [];

        foreach (Schema::getTables() as $tableInfo) {
            $childTable = $tableInfo['name'] ?? null;

            if (! is_string($childTable) || ! Schema::hasColumn($childTable, 'id')) {
                continue;
            }

            foreach (Schema::getForeignKeys($childTable) as $foreignKey) {
                $columns = $foreignKey['columns'] ?? [];
                $foreignColumns = $foreignKey['foreign_columns'] ?? [];
                $parentTable = $foreignKey['foreign_table'] ?? null;

                if (count($columns) !== 1
                    || $foreignColumns !== ['id']
                    || ! is_string($parentTable)) {
                    continue;
                }

                $dependents[$parentTable][] = [
                    'table' => $childTable,
                    'column' => $columns[0],
                ];
            }
        }

        return self::$foreignKeyDependents[$cacheKey] = $dependents;
    }

    private function isForeignKeyViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        return match (DB::connection()->getDriverName()) {
            'pgsql' => $sqlState === '23503',
            'mysql', 'mariadb' => in_array($driverCode, [1451, 1217], true),
            'sqlite' => $sqlState === '23000' && $driverCode === 19,
            'sqlsrv' => $driverCode === 547,
            default => false,
        };
    }
}

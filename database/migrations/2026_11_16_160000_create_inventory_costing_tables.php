<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('cost_of_goods_sold', 14, 2)->nullable();
        });

        foreach (['chick_batch_movements', 'parent_flock_movements', 'poultry_movements', 'egg_inventory_movements'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('total_cost', 14, 2)->nullable();
            });
        }

        Schema::create('inventory_cost_layers', function (Blueprint $table) {
            $table->id();
            $table->string('inventory_type', 30);
            $table->unsignedBigInteger('inventory_id');
            $table->string('gender', 10)->nullable();
            $table->string('layer_type', 30);
            $table->date('acquired_on')->index();
            $table->decimal('quantity', 14, 3);
            $table->decimal('remaining_quantity', 14, 3);
            $table->decimal('total_cost', 14, 2)->nullable();
            $table->decimal('remaining_cost', 14, 2)->nullable();
            $table->nullableMorphs('source');
            $table->timestamps();

            $table->index(['inventory_type', 'inventory_id', 'gender', 'acquired_on'], 'inventory_cost_layers_stock_date_idx');
        });

        Schema::create('inventory_cost_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_cost_layer_id')->nullable()->constrained('inventory_cost_layers')->restrictOnDelete();
            $table->string('inventory_type', 30);
            $table->unsignedBigInteger('inventory_id');
            $table->string('gender', 10)->nullable();
            $table->string('movement_type', 30);
            $table->decimal('quantity_delta', 14, 3)->default(0);
            $table->decimal('cost_delta', 14, 2)->nullable();
            $table->date('moved_on')->index();
            $table->nullableMorphs('source');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['inventory_type', 'inventory_id', 'gender', 'moved_on'], 'inventory_cost_movements_stock_date_idx');
            $table->index(['movement_type', 'moved_on']);
        });

        Schema::create('inventory_costing_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->date('costing_started_on');
        });
        DB::table('inventory_costing_settings')->insert([
            'id' => 1,
            'costing_started_on' => today()->toDateString(),
        ]);

        $seedOpeningLayer = function (string $type, int $id, ?string $gender, int|float $quantity): void {
            if ((float) $quantity <= 0) {
                return;
            }

            $layerId = DB::table('inventory_cost_layers')->insertGetId([
                'inventory_type' => $type,
                'inventory_id' => $id,
                'gender' => $gender,
                'layer_type' => 'opening_unvalued',
                'acquired_on' => today()->toDateString(),
                'quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'total_cost' => null,
                'remaining_cost' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('inventory_cost_movements')->insert([
                'inventory_cost_layer_id' => $layerId,
                'inventory_type' => $type,
                'inventory_id' => $id,
                'gender' => $gender,
                'movement_type' => 'opening_stock',
                'quantity_delta' => $quantity,
                'cost_delta' => null,
                'moved_on' => today()->toDateString(),
                'notes' => 'Opening stock requires valuation before it can be sold or transferred.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        };

        DB::table('parent_flocks')->orderBy('id')->each(function (object $flock) use ($seedOpeningLayer): void {
            $seedOpeningLayer('parent_flock', (int) $flock->id, 'male', (int) $flock->current_male_quantity);
            $seedOpeningLayer('parent_flock', (int) $flock->id, 'female', (int) $flock->current_female_quantity);
        });

        DB::table('poultries')->orderBy('id')->each(function (object $poultry) use ($seedOpeningLayer): void {
            $seedOpeningLayer('poultry', (int) $poultry->id, null, (int) $poultry->current_quantity);
        });

        DB::table('chick_batches')->orderBy('id')->each(function (object $batch) use ($seedOpeningLayer): void {
            $seedOpeningLayer('chick_batch', (int) $batch->id, null, (int) $batch->current_quantity);
        });

        DB::table('egg_stocks')->orderBy('id')->each(function (object $stock) use ($seedOpeningLayer): void {
            $seedOpeningLayer('egg_stock', (int) $stock->id, null, (int) $stock->good_available);
        });

        DB::table('incubation_batches')->where('status', 'incubating')->orderBy('id')->each(function (object $batch) use ($seedOpeningLayer): void {
            $seedOpeningLayer('incubation_batch', (int) $batch->id, null, (int) $batch->eggs_set);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_costing_settings');
        Schema::dropIfExists('inventory_cost_movements');
        Schema::dropIfExists('inventory_cost_layers');

        foreach (['egg_inventory_movements', 'poultry_movements', 'parent_flock_movements', 'chick_batch_movements'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('total_cost');
            });
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('cost_of_goods_sold');
        });
    }
};

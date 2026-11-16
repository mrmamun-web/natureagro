<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_cost_layers', function (Blueprint $table) {
            $table->decimal('total_production_units', 16, 3)->nullable();
            $table->decimal('remaining_production_units', 16, 3)->nullable();
        });

        Schema::table('inventory_cost_movements', function (Blueprint $table) {
            $table->decimal('production_units_delta', 16, 3)->default(0);
        });

        Schema::table('inventory_costing_settings', function (Blueprint $table) {
            $table->string('breeder_cost_method', 30)->default('units_of_production');
            $table->unsignedInteger('lifetime_good_eggs_per_hen')->default(3000);
        });

        $lifetimeGoodEggsPerHen = (int) DB::table('inventory_costing_settings')
            ->where('id', 1)
            ->value('lifetime_good_eggs_per_hen');

        DB::table('inventory_cost_layers')
            ->where('inventory_type', 'parent_flock')
            ->where('gender', 'female')
            ->where('remaining_quantity', '>', 0)
            ->orderBy('id')
            ->each(function (object $layer) use ($lifetimeGoodEggsPerHen): void {
                $units = round((float) $layer->remaining_quantity * $lifetimeGoodEggsPerHen, 3);

                DB::table('inventory_cost_layers')
                    ->where('id', $layer->id)
                    ->update([
                        'total_production_units' => $units,
                        'remaining_production_units' => $units,
                    ]);

                DB::table('inventory_cost_movements')
                    ->where('inventory_cost_layer_id', $layer->id)
                    ->where('movement_type', 'opening_stock')
                    ->update(['production_units_delta' => $units]);
            });
    }

    public function down(): void
    {
        Schema::table('inventory_costing_settings', function (Blueprint $table) {
            $table->dropColumn(['breeder_cost_method', 'lifetime_good_eggs_per_hen']);
        });

        Schema::table('inventory_cost_movements', function (Blueprint $table) {
            $table->dropColumn('production_units_delta');
        });

        Schema::table('inventory_cost_layers', function (Blueprint $table) {
            $table->dropColumn(['total_production_units', 'remaining_production_units']);
        });
    }
};

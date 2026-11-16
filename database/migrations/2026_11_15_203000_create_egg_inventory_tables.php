<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flock_daily_records', function (Blueprint $table) {
            $table->unsignedInteger('good_eggs')->default(0)->after('eggs_collected');
            $table->unsignedInteger('damaged_eggs')->default(0)->after('good_eggs');
        });

        Schema::create('egg_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_flock_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedInteger('good_available')->default(0);
            $table->unsignedInteger('damaged')->default(0);
            $table->unsignedInteger('reserved_for_hatching')->default(0);
            $table->timestamps();
        });

        Schema::create('egg_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_flock_id')->constrained()->restrictOnDelete();
            $table->string('movement_type', 30);
            $table->integer('good_delta')->default(0);
            $table->integer('damaged_delta')->default(0);
            $table->integer('reserved_delta')->default(0);
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['parent_flock_id', 'created_at']);
        });

        $legacyRecords = DB::table('flock_daily_records')
            ->where('eggs_collected', '>', 0)
            ->get(['id', 'parent_flock_id', 'eggs_collected', 'created_at', 'updated_at']);

        foreach ($legacyRecords as $record) {
            DB::table('egg_inventory_movements')->insert([
                'parent_flock_id' => $record->parent_flock_id,
                'movement_type' => 'legacy_collection',
                'good_delta' => 0,
                'damaged_delta' => 0,
                'reserved_delta' => 0,
                'source_type' => 'legacy_flock_daily_record',
                'source_id' => $record->id,
                'notes' => 'Historical egg count retained; quality was not recorded.',
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('egg_inventory_movements');
        Schema::dropIfExists('egg_stocks');

        Schema::table('flock_daily_records', function (Blueprint $table) {
            $table->dropColumn(['good_eggs', 'damaged_eggs']);
        });
    }
};

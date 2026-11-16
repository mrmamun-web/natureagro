<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poultry_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poultry_id')->constrained()->restrictOnDelete();
            $table->string('movement_type', 24);
            $table->unsignedInteger('quantity')->default(0);
            $table->foreignId('source_chick_batch_id')->nullable()->constrained('chick_batches')->restrictOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->restrictOnDelete();
            $table->date('moved_on');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['poultry_id', 'moved_on']);
        });

        Schema::create('parent_flock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_flock_id')->constrained()->restrictOnDelete();
            $table->string('movement_type', 24);
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('male_quantity')->default(0);
            $table->unsignedInteger('female_quantity')->default(0);
            $table->foreignId('source_chick_batch_id')->nullable()->constrained('chick_batches')->restrictOnDelete();
            $table->foreignId('source_poultry_id')->nullable()->constrained('poultries')->restrictOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->restrictOnDelete();
            $table->foreignId('flock_daily_record_id')->nullable()->constrained('flock_daily_records')->restrictOnDelete();
            $table->date('moved_on');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['parent_flock_id', 'moved_on']);
        });

        DB::table('poultries')->orderBy('id')->each(function (object $poultry): void {
            DB::table('poultry_movements')->insert([
                'poultry_id' => $poultry->id,
                'movement_type' => 'opening_stock',
                'quantity' => $poultry->initial_quantity,
                'moved_on' => $poultry->purchase_date ?? $poultry->created_at,
                'notes' => 'Opening poultry inventory record.',
                'created_at' => $poultry->created_at,
                'updated_at' => $poultry->updated_at,
            ]);
        });

        DB::table('parent_flocks')->orderBy('id')->each(function (object $flock): void {
            DB::table('parent_flock_movements')->insert([
                'parent_flock_id' => $flock->id,
                'movement_type' => 'opening_stock',
                'quantity' => $flock->initial_male_quantity + $flock->initial_female_quantity,
                'male_quantity' => $flock->initial_male_quantity,
                'female_quantity' => $flock->initial_female_quantity,
                'moved_on' => $flock->purchase_date ?? $flock->created_at,
                'notes' => 'Opening parent flock record.',
                'created_at' => $flock->created_at,
                'updated_at' => $flock->updated_at,
            ]);
        });

        DB::table('flock_daily_records')->orderBy('id')->each(function (object $record): void {
            $male = (int) $record->male_mortality;
            $female = (int) $record->female_mortality;
            if ($male + $female === 0) {
                return;
            }

            DB::table('parent_flock_movements')->insert([
                'parent_flock_id' => $record->parent_flock_id,
                'movement_type' => 'mortality',
                'quantity' => $male + $female,
                'male_quantity' => $male,
                'female_quantity' => $female,
                'flock_daily_record_id' => $record->id,
                'moved_on' => $record->recorded_on,
                'notes' => 'Recorded in daily flock record.',
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at,
            ]);
        });

        DB::table('sales')->whereIn('product_type', ['parent_hen', 'parent_rooster'])->orderBy('id')->each(function (object $sale): void {
            DB::table('parent_flock_movements')->insert([
                'parent_flock_id' => $sale->parent_flock_id,
                'movement_type' => 'sale',
                'quantity' => $sale->quantity,
                'male_quantity' => $sale->product_type === 'parent_rooster' ? $sale->quantity : 0,
                'female_quantity' => $sale->product_type === 'parent_hen' ? $sale->quantity : 0,
                'sale_id' => $sale->id,
                'moved_on' => $sale->sold_on,
                'notes' => 'Parent flock sale.',
                'created_at' => $sale->created_at,
                'updated_at' => $sale->updated_at,
            ]);
        });

        DB::table('sales')->where('product_type', 'poultry')->whereNotNull('poultry_id')->orderBy('id')->each(function (object $sale): void {
            DB::table('poultry_movements')->insert([
                'poultry_id' => $sale->poultry_id,
                'movement_type' => 'sale',
                'quantity' => $sale->quantity,
                'sale_id' => $sale->id,
                'moved_on' => $sale->sold_on,
                'notes' => 'Poultry inventory sale.',
                'created_at' => $sale->created_at,
                'updated_at' => $sale->updated_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_flock_movements');
        Schema::dropIfExists('poultry_movements');
    }
};

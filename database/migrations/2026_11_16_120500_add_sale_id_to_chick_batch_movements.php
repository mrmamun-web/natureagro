<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chick_batch_movements', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable()->constrained('sales')->restrictOnDelete();
        });

        DB::table('sales')->where('product_type', 'chick')->whereNotNull('chick_batch_id')->orderBy('id')->each(function (object $sale): void {
            DB::table('chick_batch_movements')->insert([
                'chick_batch_id' => $sale->chick_batch_id,
                'movement_type' => 'sale',
                'quantity' => $sale->quantity,
                'sale_id' => $sale->id,
                'moved_on' => $sale->sold_on,
                'notes' => 'Chick batch sale.',
                'created_at' => $sale->created_at,
                'updated_at' => $sale->updated_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('chick_batch_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_id');
        });
    }
};

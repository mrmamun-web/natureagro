<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            $table->decimal('minimum_stock', 12, 3)->default(0);
        });

        Schema::table('supply_usages', function (Blueprint $table) {
            $table->string('usage_type', 24)->default('feed_consumption')->index();
            $table->string('diagnosis', 150)->nullable();
            $table->string('dosage', 100)->nullable();
            $table->string('administered_by', 100)->nullable();
            $table->date('next_dose_on')->nullable()->index();
        });

        DB::table('supply_usages')
            ->whereIn('supply_product_id', DB::table('supply_products')->where('type', 'medicine')->select('id'))
            ->update(['usage_type' => 'treatment']);
    }

    public function down(): void
    {
        Schema::table('supply_usages', function (Blueprint $table) {
            $table->dropColumn(['usage_type', 'diagnosis', 'dosage', 'administered_by', 'next_dose_on']);
        });

        Schema::table('supply_products', function (Blueprint $table) {
            $table->dropColumn('minimum_stock');
        });
    }
};

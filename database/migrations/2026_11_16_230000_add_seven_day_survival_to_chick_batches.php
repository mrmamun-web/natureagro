<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chick_batches', function (Blueprint $table): void {
            $table->unsignedInteger('survived_after_7_days')->nullable();
            $table->date('survival_recorded_on')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('chick_batches', function (Blueprint $table): void {
            $table->dropColumn(['survived_after_7_days', 'survival_recorded_on']);
        });
    }
};

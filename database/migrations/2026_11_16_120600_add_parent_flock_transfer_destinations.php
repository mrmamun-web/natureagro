<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poultry_movements', function (Blueprint $table) {
            $table->foreignId('source_parent_flock_id')->nullable()->constrained('parent_flocks')->restrictOnDelete();
        });

        Schema::table('parent_flock_movements', function (Blueprint $table) {
            $table->foreignId('target_poultry_id')->nullable()->constrained('poultries')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('parent_flock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('target_poultry_id');
        });

        Schema::table('poultry_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_parent_flock_id');
        });
    }
};

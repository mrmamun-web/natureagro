<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_flocks', function (Blueprint $table) {
            $table->id();
            $table->string('flock_code', 50)->unique();
            $table->string('name', 150);
            $table->string('breed', 100)->nullable();
            $table->unsignedInteger('initial_male_quantity');
            $table->unsignedInteger('initial_female_quantity');
            $table->unsignedInteger('current_male_quantity');
            $table->unsignedInteger('current_female_quantity');
            $table->date('purchase_date')->nullable();
            $table->string('health_status', 20)->default('healthy')->index();
            $table->string('status', 20)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'health_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_flocks');
    }
};

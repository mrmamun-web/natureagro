<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poultries', function (Blueprint $table) {
            $table->id();

            // Identification
            $table->string('poultry_code', 50)->unique();
            $table->string('name', 150);

            // Poultry Information
            $table->string('type', 30)->index();
            $table->string('breed', 100)->nullable();

            // Quantity Management
            $table->unsignedInteger('initial_quantity');
            $table->unsignedInteger('current_quantity');

            // Purchase Information
            $table->date('purchase_date')->nullable();
            $table->decimal('unit_purchase_price', 12, 2)->nullable();

            // Age Information
            $table->date('date_of_birth')->nullable();

            // Status
            $table->string('status', 20)->default('active')->index();

            // Additional Information
            $table->text('notes')->nullable();

            $table->timestamps();

            // Query optimization
            $table->index(['type', 'status']);
            $table->index('purchase_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poultries');
    }
};

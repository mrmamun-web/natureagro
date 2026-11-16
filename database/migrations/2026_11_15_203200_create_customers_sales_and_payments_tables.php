<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('phone_number', 30)->nullable()->index();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('product_type', 30)->index();
            $table->foreignId('parent_flock_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('poultry_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('chick_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_weight_kg', 10, 3)->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->string('payment_status', 20)->default('due')->index();
            $table->string('payment_method', 20)->nullable();
            $table->date('sold_on');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_type', 'sold_on']);
            $table->index(['customer_id', 'sold_on']);
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_on');
            $table->string('payment_method', 20);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'paid_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('customers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('phone_number', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('supply_products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('type', 20)->index();
            $table->string('unit', 30);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('supply_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_number', 50)->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('supply_product_id')->constrained()->restrictOnDelete();
            $table->date('purchased_on')->index();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2);
            $table->string('payment_status', 20)->index();
            $table->string('payment_method', 20)->nullable();
            $table->string('supplier_invoice', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'purchased_on']);
        });

        Schema::create('supply_purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_purchase_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_on');
            $table->string('payment_method', 20);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['supply_purchase_id', 'paid_on']);
        });

        Schema::create('supply_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_product_id')->constrained()->restrictOnDelete();
            $table->foreignId('supply_purchase_id')->unique()->constrained()->restrictOnDelete();
            $table->date('received_on')->index();
            $table->date('expires_on')->nullable()->index();
            $table->decimal('initial_quantity', 12, 3);
            $table->decimal('remaining_quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2);
            $table->timestamps();

            $table->index(['supply_product_id', 'received_on', 'id']);
        });

        Schema::create('supply_usages', function (Blueprint $table) {
            $table->id();
            $table->string('usage_number', 50)->unique();
            $table->foreignId('supply_product_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_flock_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('chick_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('used_on')->index();
            $table->decimal('quantity', 12, 3);
            $table->decimal('total_cost', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['parent_flock_id', 'used_on']);
            $table->index(['chick_batch_id', 'used_on']);
        });

        Schema::create('supply_usage_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_usage_id')->constrained()->restrictOnDelete();
            $table->foreignId('supply_lot_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_cost', 12, 2);
            $table->timestamps();

            $table->unique(['supply_usage_id', 'supply_lot_id']);
        });

        Schema::create('supply_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_product_id')->constrained()->restrictOnDelete();
            $table->foreignId('supply_lot_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('movement_type', 20)->index();
            $table->decimal('quantity_delta', 12, 3);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->nullableMorphs('source');
            $table->date('moved_on')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['supply_product_id', 'moved_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_stock_movements');
        Schema::dropIfExists('supply_usage_lots');
        Schema::dropIfExists('supply_usages');
        Schema::dropIfExists('supply_lots');
        Schema::dropIfExists('supply_purchase_payments');
        Schema::dropIfExists('supply_purchases');
        Schema::dropIfExists('supply_products');
        Schema::dropIfExists('suppliers');
    }
};

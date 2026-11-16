<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type', 20)->index();
            $table->string('category', 40)->index();
            $table->decimal('amount', 12, 2);
            $table->date('transaction_on')->index();
            $table->string('payment_method', 20)->nullable();
            $table->string('reference', 100)->nullable();
            $table->foreignId('reversal_of_id')->nullable()->unique()->constrained('farm_transactions')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_transactions');
    }
};

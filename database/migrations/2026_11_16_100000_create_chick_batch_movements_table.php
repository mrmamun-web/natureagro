<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chick_batch_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chick_batch_id')->constrained()->restrictOnDelete();
            $table->string('movement_type', 20);
            $table->unsignedInteger('quantity')->default(0);
            $table->string('gender', 10)->nullable();
            $table->foreignId('source_chick_batch_id')->nullable()->constrained('chick_batches')->restrictOnDelete();
            $table->foreignId('target_chick_batch_id')->nullable()->constrained('chick_batches')->restrictOnDelete();
            $table->foreignId('target_parent_flock_id')->nullable()->constrained('parent_flocks')->restrictOnDelete();
            $table->foreignId('target_poultry_id')->nullable()->constrained('poultries')->restrictOnDelete();
            $table->date('moved_on');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['chick_batch_id', 'moved_on']);
            $table->index('movement_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chick_batch_movements');
    }
};

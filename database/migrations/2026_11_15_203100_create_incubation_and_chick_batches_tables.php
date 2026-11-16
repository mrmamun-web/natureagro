<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incubation_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 50)->unique();
            $table->foreignId('parent_flock_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('eggs_set');
            $table->unsignedInteger('hatched_chicks')->default(0);
            $table->unsignedInteger('failed_eggs')->default(0);
            $table->date('incubated_on');
            $table->date('expected_hatch_on');
            $table->date('actual_hatch_on')->nullable();
            $table->string('status', 20)->default('incubating')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['parent_flock_id', 'incubated_on']);
        });

        Schema::create('chick_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 50)->unique();
            $table->string('name', 150);
            $table->string('breed', 100)->nullable();
            $table->string('source', 20);
            $table->foreignId('source_incubation_batch_id')->nullable()->unique()->constrained('incubation_batches')->restrictOnDelete();
            $table->date('hatch_date');
            $table->unsignedInteger('initial_quantity');
            $table->unsignedInteger('current_quantity');
            $table->string('status', 20)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'hatch_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chick_batches');
        Schema::dropIfExists('incubation_batches');
    }
};

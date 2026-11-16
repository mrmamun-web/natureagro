<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flock_daily_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_flock_id')->constrained()->restrictOnDelete();
            $table->date('recorded_on');
            $table->unsignedInteger('male_mortality')->default(0);
            $table->unsignedInteger('female_mortality')->default(0);
            $table->unsignedInteger('eggs_collected')->default(0);
            $table->decimal('feed_consumed_kg', 10, 2)->default(0);
            $table->string('health_status', 20);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['parent_flock_id', 'recorded_on']);
            $table->index('recorded_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flock_daily_records');
    }
};

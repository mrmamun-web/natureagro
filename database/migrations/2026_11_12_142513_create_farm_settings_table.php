<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_settings', function (Blueprint $table) {
            $table->id();

            $table->string('farm_name');
            $table->string('owner_name');
            $table->string('phone_number', 20)->nullable();

            $table->text('address')->nullable();
            $table->text('description')->nullable();

            $table->string('currency', 3)->default('BDT');

            $table->string('farm_logo')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_settings');
    }
};

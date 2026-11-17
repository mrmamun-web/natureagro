<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incubation_batches', function (Blueprint $table): void {
            $table->unsignedTinyInteger('incubation_month')->nullable()->after('incubated_on');
        });

        Schema::table('chick_batches', function (Blueprint $table): void {
            $table->unsignedTinyInteger('batch_month')->nullable()->after('hatch_date');
        });

        DB::table('incubation_batches')
            ->select(['id', 'incubated_on'])
            ->whereNull('incubation_month')
            ->orderBy('id')
            ->chunkById(100, function ($batches): void {
                foreach ($batches as $batch) {
                    DB::table('incubation_batches')
                        ->where('id', $batch->id)
                        ->update(['incubation_month' => Carbon::parse($batch->incubated_on)->month]);
                }
            });

        DB::table('chick_batches')
            ->select(['id', 'hatch_date'])
            ->whereNull('batch_month')
            ->orderBy('id')
            ->chunkById(100, function ($batches): void {
                foreach ($batches as $batch) {
                    DB::table('chick_batches')
                        ->where('id', $batch->id)
                        ->update(['batch_month' => Carbon::parse($batch->hatch_date)->month]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('chick_batches', function (Blueprint $table): void {
            $table->dropColumn('batch_month');
        });

        Schema::table('incubation_batches', function (Blueprint $table): void {
            $table->dropColumn('incubation_month');
        });
    }
};

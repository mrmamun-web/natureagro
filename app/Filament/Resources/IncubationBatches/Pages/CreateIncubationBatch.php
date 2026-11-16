<?php

namespace App\Filament\Resources\IncubationBatches\Pages;

use App\Filament\Resources\IncubationBatches\IncubationBatchResource;
use App\Models\IncubationBatch;
use App\Models\ParentFlock;
use App\Services\EggInventoryService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateIncubationBatch extends CreateRecord
{
    protected static string $resource = IncubationBatchResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): IncubationBatch {
            $flock = ParentFlock::query()
                ->lockForUpdate()
                ->findOrFail($data['parent_flock_id']);

            if ($flock->status !== ParentFlock::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'data.parent_flock_id' => 'Only active flocks can supply eggs for incubation.',
                ]);
            }

            $batch = $flock->incubationBatches()->create($data);
            app(EggInventoryService::class)->reserveForIncubation($batch);

            return $batch;
        });
    }
}

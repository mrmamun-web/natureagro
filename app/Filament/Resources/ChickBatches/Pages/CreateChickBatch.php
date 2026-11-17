<?php

namespace App\Filament\Resources\ChickBatches\Pages;

use App\Filament\Resources\ChickBatches\ChickBatchResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateChickBatch extends CreateRecord
{
    protected static string $resource = ChickBatchResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        throw ValidationException::withMessages([
            'data' => 'Chick batches are created automatically from recorded hatch outcomes.',
        ]);
    }
}

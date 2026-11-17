<?php

namespace App\Filament\Resources\ParentFlocks\Pages;

use App\Filament\Resources\ParentFlocks\ParentFlockResource;
use App\Services\ParentFlockService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditParentFlock extends EditRecord
{
    protected static string $resource = ParentFlockResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(ParentFlockService::class)->updatePopulation($record, $data);
    }
}

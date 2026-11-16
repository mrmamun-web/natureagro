<?php

namespace App\Filament\Resources\Poultries\Pages;

use App\Filament\Resources\Poultries\PoultryResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPoultry extends EditRecord
{
    protected static string $resource = PoultryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}

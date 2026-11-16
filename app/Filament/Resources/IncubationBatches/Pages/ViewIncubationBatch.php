<?php

namespace App\Filament\Resources\IncubationBatches\Pages;

use App\Filament\Resources\IncubationBatches\IncubationBatchResource;
use App\Models\IncubationBatch;
use App\Services\EggInventoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewIncubationBatch extends ViewRecord
{
    protected static string $resource = IncubationBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recordHatchOutcome')
                ->label('Record hatch result')
                ->icon('heroicon-o-check-circle')
                ->visible(fn (): bool => $this->getRecord()->status === IncubationBatch::STATUS_INCUBATING)
                ->form([
                    DatePicker::make('actual_hatch_on')
                        ->label('Actual Hatch Date')
                        ->default(now()->toDateString())
                        ->minDate(fn () => $this->getRecord()->incubated_on)
                        ->maxDate(now())
                        ->native(false)
                        ->required(),

                    TextInput::make('hatched_chicks')
                        ->label('Successful Hatches')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->required(),

                    TextInput::make('failed_eggs')
                        ->label('Failed Eggs')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $batch = app(EggInventoryService::class)->recordHatchOutcome(
                        (int) $this->getRecord()->getKey(),
                        $data,
                    );

                    Notification::make()
                        ->success()
                        ->title($batch
                            ? 'Hatch recorded. Chick batch '.$batch->batch_code.' created.'
                            : 'Hatch recorded. No chicks hatched.')
                        ->send();
                }),
        ];
    }
}

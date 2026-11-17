<?php

namespace App\Filament\Resources\ChickBatches\Pages;

use App\Filament\Resources\ChickBatches\ChickBatchResource;
use App\Models\ChickBatch;
use App\Models\ParentFlock;
use App\Models\Poultry;
use App\Services\ChickBatchService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;

class ViewChickBatch extends ViewRecord
{
    protected static string $resource = ChickBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recordSevenDaySurvival')
                ->label('Record / edit 7-day survival')
                ->icon('heroicon-o-check-circle')
                ->visible(fn (): bool => today()->greaterThanOrEqualTo(
                    $this->getRecord()->hatch_date->copy()->addDays(7),
                ))
                ->form([
                    DatePicker::make('survival_recorded_on')
                        ->label('7-day check date')
                        ->default(fn (): string => $this->getRecord()->hatch_date->copy()->addDays(7)->toDateString())
                        ->minDate(fn (): string => $this->getRecord()->hatch_date->copy()->addDays(7)->toDateString())
                        ->maxDate(today())
                        ->native(false)
                        ->required(),
                    TextInput::make('survived_after_7_days')
                        ->label('Chicks alive at the 7-day check')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->maxValue(fn (): int => $this->getRecord()->initial_quantity)
                        ->required(),
                ])
                ->fillForm(fn (): array => [
                    'survival_recorded_on' => $this->getRecord()->survival_recorded_on?->toDateString()
                        ?? $this->getRecord()->hatch_date->copy()->addDays(7)->toDateString(),
                    'survived_after_7_days' => $this->getRecord()->survived_after_7_days,
                ])
                ->action(function (array $data): void {
                    $this->getRecord()->update($data);
                    $this->record = $this->getRecord()->refresh();
                    Notification::make()
                        ->success()
                        ->title('7-day chick survival saved.')
                        ->send();
                }),
            Action::make('recordMortality')
                ->label('Record mortality')
                ->icon('heroicon-o-heart')
                ->visible(fn (): bool => $this->getRecord()->status === ChickBatch::STATUS_ACTIVE)
                ->form([
                    TextInput::make('quantity')->label('Deaths')->numeric()->integer()->minValue(1)->maxValue(fn (): int => $this->getRecord()->current_quantity)->required(),
                    DatePicker::make('moved_on')->label('Date')->default(now()->toDateString())->maxDate(now())->native(false)->required(),
                    Textarea::make('notes')->rows(2),
                ])
                ->action(function (array $data): void {
                    $this->record = app(ChickBatchService::class)->recordMortality($this->getRecord(), $data);
                    Notification::make()->success()->title('Mortality recorded and stock updated.')->send();
                }),
            Action::make('transfer')
                ->label('Transfer birds')
                ->icon('heroicon-o-arrows-right-left')
                ->visible(fn (): bool => $this->getRecord()->status === ChickBatch::STATUS_ACTIVE && $this->getRecord()->current_quantity > 0)
                ->form([
                    Select::make('target_type')
                        ->label('Transfer to')
                        ->options([
                            'chick_batch' => 'Another chick batch',
                            'parent_flock' => 'Parent flock',
                            'poultry' => 'Poultry inventory',
                        ])
                        ->live()
                        ->required(),
                    TextInput::make('quantity')
                        ->label('Birds to transfer')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(fn (): int => $this->getRecord()->current_quantity)
                        ->required(),
                    Select::make('gender')
                        ->label('Sex')
                        ->options(['female' => 'Female', 'male' => 'Male'])
                        ->visible(fn (Get $get): bool => $get('target_type') === 'parent_flock')
                        ->required(fn (Get $get): bool => $get('target_type') === 'parent_flock'),
                    Select::make('target_chick_batch_id')
                        ->label('Destination batch')
                        ->options(fn (): array => ChickBatch::query()
                            ->where('status', ChickBatch::STATUS_ACTIVE)
                            ->whereKeyNot($this->getRecord()->getKey())
                            ->orderBy('hatch_date')
                            ->pluck('batch_code', 'id')
                            ->all())
                        ->searchable()
                        ->visible(fn (Get $get): bool => $get('target_type') === 'chick_batch')
                        ->required(fn (Get $get): bool => $get('target_type') === 'chick_batch'),
                    Select::make('target_parent_flock_id')
                        ->label('Destination parent flock')
                        ->options(fn (): array => ParentFlock::query()->active()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->visible(fn (Get $get): bool => $get('target_type') === 'parent_flock')
                        ->required(fn (Get $get): bool => $get('target_type') === 'parent_flock'),
                    Select::make('target_poultry_id')
                        ->label('Destination poultry inventory')
                        ->options(fn (): array => Poultry::query()->active()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->visible(fn (Get $get): bool => $get('target_type') === 'poultry')
                        ->required(fn (Get $get): bool => $get('target_type') === 'poultry'),
                    DatePicker::make('moved_on')->label('Date')->default(now()->toDateString())->maxDate(now())->native(false)->required(),
                    Textarea::make('notes')->rows(2),
                ])
                ->action(function (array $data): void {
                    $this->record = app(ChickBatchService::class)->transfer($this->getRecord(), $data);
                    Notification::make()->success()->title('Bird transfer recorded and stock updated.')->send();
                }),
            Action::make('archive')
                ->label('Archive batch')
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status === ChickBatch::STATUS_ACTIVE)
                ->action(function (): void {
                    $this->record = app(ChickBatchService::class)->archive($this->getRecord());
                    Notification::make()->success()->title('Batch archived. History remains available.')->send();
                }),
        ];
    }
}

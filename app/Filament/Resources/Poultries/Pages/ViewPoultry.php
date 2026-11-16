<?php

namespace App\Filament\Resources\Poultries\Pages;

use App\Filament\Resources\Poultries\PoultryResource;
use App\Models\ParentFlock;
use App\Models\Poultry;
use App\Services\PoultryStockService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPoultry extends ViewRecord
{
    protected static string $resource = PoultryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('recordMortality')
                ->label('Record mortality')
                ->icon('heroicon-o-heart')
                ->visible(fn (): bool => $this->getRecord()->status === Poultry::STATUS_ACTIVE && $this->getRecord()->current_quantity > 0)
                ->form([
                    TextInput::make('quantity')->label('Deaths')->numeric()->integer()->minValue(1)->maxValue(fn (): int => $this->getRecord()->current_quantity)->required(),
                    DatePicker::make('moved_on')->label('Date')->default(now()->toDateString())->maxDate(now())->native(false)->required(),
                    Textarea::make('notes')->rows(2),
                ])
                ->action(function (array $data): void {
                    $this->record = app(PoultryStockService::class)->recordMortality($this->getRecord(), $data);
                    Notification::make()->success()->title('Mortality recorded and stock updated.')->send();
                }),
            Action::make('transferToFlock')
                ->label('Transfer to parent flock')
                ->icon('heroicon-o-arrows-right-left')
                ->visible(fn (): bool => $this->getRecord()->status === Poultry::STATUS_ACTIVE && $this->getRecord()->current_quantity > 0)
                ->form([
                    TextInput::make('quantity')->label('Birds to transfer')->numeric()->integer()->minValue(1)->maxValue(fn (): int => $this->getRecord()->current_quantity)->required(),
                    Select::make('target_parent_flock_id')
                        ->label('Destination flock')
                        ->options(fn (): array => ParentFlock::query()->active()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                    DatePicker::make('moved_on')->label('Date')->default(now()->toDateString())->maxDate(now())->native(false)->required(),
                    Textarea::make('notes')->rows(2),
                ])
                ->action(function (array $data): void {
                    $this->record = app(PoultryStockService::class)->transferToParentFlock($this->getRecord(), $data);
                    Notification::make()->success()->title('Transfer recorded and flock stock updated.')->send();
                }),
        ];
    }
}

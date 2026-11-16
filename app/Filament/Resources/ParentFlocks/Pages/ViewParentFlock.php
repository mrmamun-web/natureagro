<?php

namespace App\Filament\Resources\ParentFlocks\Pages;

use App\Filament\Resources\ParentFlocks\ParentFlockResource;
use App\Models\ParentFlock;
use App\Models\Poultry;
use App\Services\ParentFlockService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;

class ViewParentFlock extends ViewRecord
{
    protected static string $resource = ParentFlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('transferToPoultry')
                ->label('Transfer to poultry inventory')
                ->icon('heroicon-o-arrows-right-left')
                ->visible(fn (): bool => $this->getRecord()->status === ParentFlock::STATUS_ACTIVE)
                ->form([
                    Select::make('gender')
                        ->label('Sex')
                        ->options(['female' => 'Female', 'male' => 'Male'])
                        ->live()
                        ->required(),
                    TextInput::make('quantity')
                        ->label('Birds to transfer')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(fn (Get $get): int => (int) ($get('gender') === 'male'
                            ? $this->getRecord()->current_male_quantity
                            : $this->getRecord()->current_female_quantity))
                        ->required(),
                    Select::make('target_poultry_id')
                        ->label('Destination inventory')
                        ->options(fn (Get $get): array => Poultry::query()
                            ->active()
                            ->when(filled($get('gender')), fn ($query) => $query->where('type', $get('gender') === 'male' ? Poultry::TYPE_ROOSTER : Poultry::TYPE_CHICKEN))
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (Poultry $poultry): array => [
                                $poultry->id => $poultry->name.' ('.$poultry->current_quantity.' available)',
                            ])
                            ->all())
                        ->searchable()
                        ->required(),
                    DatePicker::make('moved_on')->label('Date')->default(now()->toDateString())->maxDate(now())->native(false)->required(),
                    Textarea::make('notes')->rows(2),
                ])
                ->action(function (array $data): void {
                    $this->record = app(ParentFlockService::class)->transferToPoultryInventory($this->getRecord(), $data);
                    Notification::make()->success()->title('Transfer recorded and flock stock updated.')->send();
                }),
        ];
    }
}

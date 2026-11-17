<?php

namespace App\Filament\Pages;

use App\Models\FarmSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FarmSettings extends Page
{
    protected string $view = 'filament.pages.farm-settings';

    protected static ?string $navigationLabel = 'Farm Settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports & Settings';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'Farm Settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(
            $this->getRecord()?->attributesToArray()
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Farm Information')
                        ->description('Manage your farm identity and contact information.')
                        ->schema([
                            TextInput::make('farm_name')
                                ->label('Farm Name')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('owner_name')
                                ->label('Owner Name')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('phone_number')
                                ->label('Phone Number')
                                ->tel()
                                ->regex('/^[0-9+\-\s()]+$/')
                                ->maxLength(20),

                            Textarea::make('address')
                                ->label('Farm Address')
                                ->rows(3),

                            Textarea::make('description')
                                ->label('Farm Description')
                                ->rows(4),
                        ])
                        ->columns(2),

                    Section::make('Regional Settings')
                        ->schema([
                            Select::make('currency')
                                ->label('Currency')
                                ->options([
                                    'BDT' => 'BDT - Bangladeshi Taka',
                                ])
                                ->required(),
                        ]),

                    Section::make('Farm Logo')
                        ->schema([
                            FileUpload::make('farm_logo')
                                ->label('Upload Farm Logo')
                                ->image()
                                ->disk('public')
                                ->directory('farm-logos')
                                ->imageEditor()
                                ->maxSize(2048),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Save Farm Settings')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->record($this->getRecord())
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $record = $this->getRecord() ?? new FarmSetting;

        $record->fill($data);
        $record->save();

        if ($record->wasRecentlyCreated) {
            $this->form->record($record)->saveRelationships();
        }

        Notification::make()
            ->success()
            ->title('Farm settings saved successfully!')
            ->send();
    }

    public function getRecord(): ?FarmSetting
    {
        return FarmSetting::query()->first();
    }
}

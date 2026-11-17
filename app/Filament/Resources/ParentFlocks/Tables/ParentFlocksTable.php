<?php

namespace App\Filament\Resources\ParentFlocks\Tables;

use App\Models\ParentFlock;
use App\Support\SafeDeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ParentFlocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('flock_code')
                    ->label('Flock Code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('Flock Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('breed')
                    ->label('Breed')
                    ->placeholder('Not specified'),

                TextColumn::make('current_male_quantity')
                    ->label('Males')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('current_female_quantity')
                    ->label('Females')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('current_quantity')
                    ->label('Total Birds')
                    ->numeric()
                    ->sortable(query: fn ($query, string $direction) => $query->orderByRaw("(current_male_quantity + current_female_quantity) {$direction}"))
                    ->weight('bold'),

                TextColumn::make('health_status')
                    ->label('Health')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ParentFlock::HEALTHY => 'success',
                        ParentFlock::HEALTH_MONITORING => 'warning',
                        ParentFlock::HEALTH_CRITICAL => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === ParentFlock::STATUS_ACTIVE ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        ParentFlock::STATUS_ACTIVE => 'Active',
                        ParentFlock::STATUS_INACTIVE => 'Inactive',
                    ]),

                SelectFilter::make('health_status')
                    ->label('Health')
                    ->options([
                        ParentFlock::HEALTHY => 'Healthy',
                        ParentFlock::HEALTH_MONITORING => 'Needs Monitoring',
                        ParentFlock::HEALTH_CRITICAL => 'Critical',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                SafeDeleteAction::make('parent flock'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

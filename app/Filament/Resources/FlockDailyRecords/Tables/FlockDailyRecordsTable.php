<?php

namespace App\Filament\Resources\FlockDailyRecords\Tables;

use App\Models\ParentFlock;
use App\Support\SafeDeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FlockDailyRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('recorded_on')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('parentFlock.name')
                    ->label('Parent Flock')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('male_mortality')
                    ->label('Male Deaths')
                    ->numeric(),

                TextColumn::make('female_mortality')
                    ->label('Female Deaths')
                    ->numeric(),

                TextColumn::make('good_eggs')
                    ->label('Good Eggs')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('damaged_eggs')
                    ->label('Damaged Eggs')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('feed_consumed_kg')
                    ->label('Feed (kg)')
                    ->numeric(decimalPlaces: 2),

                TextColumn::make('health_status')
                    ->label('Health')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ParentFlock::HEALTHY => 'success',
                        ParentFlock::HEALTH_MONITORING => 'warning',
                        ParentFlock::HEALTH_CRITICAL => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('parent_flock_id')
                    ->label('Parent Flock')
                    ->relationship('parentFlock', 'name'),

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
                SafeDeleteAction::make('daily flock record'),
            ])
            ->defaultSort('recorded_on', 'desc');
    }
}

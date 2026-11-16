<?php

namespace App\Filament\Resources\ChickBatches\Tables;

use App\Models\ChickBatch;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ChickBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('batch_code')
                    ->label('Batch Code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('Batch')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('breed')
                    ->label('Breed')
                    ->placeholder('Not specified'),

                TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('hatch_date')
                    ->label('Hatch Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('age_months')
                    ->label('Age')
                    ->formatStateUsing(fn (int $state): string => $state.' mo.'),

                TextColumn::make('current_quantity')
                    ->label('Alive')
                    ->numeric()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === ChickBatch::STATUS_ACTIVE ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->options([
                        ChickBatch::SOURCE_HATCHED => 'Hatched',
                        ChickBatch::SOURCE_PURCHASED => 'Purchased',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        ChickBatch::STATUS_ACTIVE => 'Active',
                        ChickBatch::STATUS_ARCHIVED => 'Archived',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('hatch_date', 'desc');
    }
}

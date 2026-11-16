<?php

namespace App\Filament\Resources\IncubationBatches\Tables;

use App\Models\IncubationBatch;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IncubationBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('batch_code')
                    ->label('Incubation Batch')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('parentFlock.name')
                    ->label('Source Flock')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('eggs_set')
                    ->label('Eggs Set')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('expected_hatch_on')
                    ->label('Expected Hatch')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('hatched_chicks')
                    ->label('Hatched')
                    ->numeric()
                    ->placeholder('—'),

                TextColumn::make('hatch_rate')
                    ->label('Success')
                    ->formatStateUsing(fn (?float $state): ?string => $state === null ? null : number_format($state, 1).'%')
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === IncubationBatch::STATUS_HATCHED ? 'success' : 'warning'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        IncubationBatch::STATUS_INCUBATING => 'Incubating',
                        IncubationBatch::STATUS_HATCHED => 'Hatched',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('incubated_on', 'desc');
    }
}

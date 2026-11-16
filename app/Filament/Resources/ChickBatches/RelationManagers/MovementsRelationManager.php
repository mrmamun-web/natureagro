<?php

namespace App\Filament\Resources\ChickBatches\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    protected static ?string $title = 'Batch history';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('moved_on')->label('Date')->date('d M Y')->sortable(),
                TextColumn::make('movement_type')->label('Activity')->badge(),
                TextColumn::make('quantity')->label('Birds')->numeric(),
                TextColumn::make('total_cost')->label('Cost assigned / relieved')->money('BDT')->placeholder('—'),
                TextColumn::make('gender')->label('Gender')->placeholder('—'),
                TextColumn::make('source_chick_batch.batch_code')->label('Source batch')->placeholder('—'),
                TextColumn::make('target_chick_batch.batch_code')->label('Destination batch')->placeholder('—'),
                TextColumn::make('target_parent_flock.name')->label('Destination flock')->placeholder('—'),
                TextColumn::make('target_poultry.name')->label('Poultry inventory')->placeholder('—'),
                TextColumn::make('notes')->label('Notes')->limit(40)->placeholder('—'),
            ])
            ->defaultSort('moved_on', 'desc');
    }
}

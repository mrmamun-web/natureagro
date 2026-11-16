<?php

namespace App\Filament\Resources\ParentFlocks\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    protected static ?string $title = 'Flock stock history';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('moved_on')->label('Date')->date('d M Y')->sortable(),
                TextColumn::make('movement_type')->label('Activity')->badge(),
                TextColumn::make('male_quantity')->label('Males')->numeric(),
                TextColumn::make('female_quantity')->label('Females')->numeric(),
                TextColumn::make('total_cost')->label('Cost assigned / relieved')->money('BDT')->placeholder('—'),
                TextColumn::make('source_chick_batch.batch_code')->label('Source batch')->placeholder('—'),
                TextColumn::make('source_poultry.poultry_code')->label('Source inventory')->placeholder('—'),
                TextColumn::make('target_poultry.poultry_code')->label('Destination inventory')->placeholder('—'),
                TextColumn::make('sale.invoice_number')->label('Invoice')->placeholder('—'),
                TextColumn::make('notes')->label('Notes')->limit(40)->placeholder('—'),
            ])
            ->defaultSort('moved_on', 'desc');
    }
}

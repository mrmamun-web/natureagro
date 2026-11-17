<?php

namespace App\Support;

use App\Exceptions\RecordHasDependencies;
use App\Services\SafeRecordDeletionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class SafeDeleteAction
{
    public static function make(string $recordName): Action
    {
        return Action::make('delete')
            ->label('Delete')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete '.$recordName)
            ->modalDescription(match ($recordName) {
                'daily flock record' => 'This permanently removes the daily record and linked egg, flock, cost, incubation, hatch, and sale history.',
                'incubation batch' => 'This permanently removes the incubation and any linked chick batches, sales, stock, and cost history.',
                'parent flock' => 'This permanently removes the flock and all linked production, hatch, sale, feed, payment, stock, and cost history.',
                default => 'This permanently removes the record and all linked records, including related stock, costs, sales, payments, and history.',
            })
            ->action(function (Model $record) use ($recordName): void {
                try {
                    app(SafeRecordDeletionService::class)->delete($record);
                } catch (RecordHasDependencies $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Delete could not be completed')
                        ->body($exception->getMessage())
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title(ucfirst($recordName).' deleted.')
                    ->send();
            });
    }
}

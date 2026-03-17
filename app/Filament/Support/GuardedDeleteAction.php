<?php

namespace App\Filament\Support;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class GuardedDeleteAction
{
    public static function make(): DeleteAction
    {
        return DeleteAction::make()
            ->before(function (DeleteAction $action, Model $record): void {
                if (! method_exists($record, 'hasDeletionDependencies') || ! $record->hasDeletionDependencies()) {
                    return;
                }

                $message = method_exists($record, 'getDeletionDependencyMessage')
                    ? $record->getDeletionDependencyMessage()
                    : 'Delete the child items first before removing this record.';

                Notification::make()
                    ->danger()
                    ->title('Delete child items first')
                    ->body($message)
                    ->send();

                $action->cancel();
            });
    }
}

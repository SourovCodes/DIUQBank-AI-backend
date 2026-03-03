<?php

namespace App\Filament\Support;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;

class DeleteDependencyGuard
{
    public static function cancelSingle(DeleteAction $action, string $resource, string $related): void
    {
        Notification::make()
            ->title("Cannot delete {$resource}")
            ->body("Delete related {$related} first.")
            ->danger()
            ->send();

        $action->cancel();
    }

    public static function cancelBulk(DeleteBulkAction $action, string $resources, string $related): void
    {
        Notification::make()
            ->title("Some {$resources} cannot be deleted")
            ->body("Delete related {$related} first, then try again.")
            ->danger()
            ->send();

        $action->cancel();
    }
}

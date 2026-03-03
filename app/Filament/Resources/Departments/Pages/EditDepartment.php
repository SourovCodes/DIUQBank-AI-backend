<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Support\DeleteDependencyGuard;
use App\Models\Department;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDepartment extends EditRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, Department $record): void {
                    if (! $record->hasDeletionDependencies()) {
                        return;
                    }

                    DeleteDependencyGuard::cancelSingle($action, 'department', 'courses and questions');
                }),
        ];
    }
}

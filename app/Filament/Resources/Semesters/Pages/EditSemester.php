<?php

namespace App\Filament\Resources\Semesters\Pages;

use App\Filament\Resources\Semesters\SemesterResource;
use App\Filament\Support\DeleteDependencyGuard;
use App\Models\Semester;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSemester extends EditRecord
{
    protected static string $resource = SemesterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, Semester $record): void {
                    if (! $record->hasDeletionDependencies()) {
                        return;
                    }

                    DeleteDependencyGuard::cancelSingle($action, 'semester', 'questions');
                }),
        ];
    }
}

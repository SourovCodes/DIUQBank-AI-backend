<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Support\DeleteDependencyGuard;
use App\Models\Course;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, Course $record): void {
                    if (! $record->hasDeletionDependencies()) {
                        return;
                    }

                    DeleteDependencyGuard::cancelSingle($action, 'course', 'questions');
                }),
        ];
    }
}

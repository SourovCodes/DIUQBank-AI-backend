<?php

namespace App\Filament\Resources\ExamTypes\Pages;

use App\Filament\Resources\ExamTypes\ExamTypeResource;
use App\Filament\Support\DeleteDependencyGuard;
use App\Models\ExamType;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExamType extends EditRecord
{
    protected static string $resource = ExamTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, ExamType $record): void {
                    if (! $record->hasDeletionDependencies()) {
                        return;
                    }

                    DeleteDependencyGuard::cancelSingle($action, 'exam type', 'questions');
                }),
        ];
    }
}

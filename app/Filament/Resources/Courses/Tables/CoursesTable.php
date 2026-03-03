<?php

namespace App\Filament\Resources\Courses\Tables;

use App\Filament\Support\DeleteDependencyGuard;
use App\Models\Course;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('department.name')
                    ->label('Department')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Course name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('questions_count')
                    ->label('Questions')
                    ->counts('questions')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No courses found')
            ->emptyStateDescription('Create your first course and assign it to a department.')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, Course $record): void {
                        if (! $record->hasDeletionDependencies()) {
                            return;
                        }

                        DeleteDependencyGuard::cancelSingle($action, 'course', 'questions');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->accessSelectedRecords()
                        ->before(function (DeleteBulkAction $action): void {
                            $blockedCount = $action->getSelectedRecordsQuery()
                                ->hasDeletionDependencies()
                                ->count();

                            if ($blockedCount === 0) {
                                return;
                            }

                            DeleteDependencyGuard::cancelBulk($action, 'courses', 'questions');
                        }),
                ]),
            ]);
    }
}

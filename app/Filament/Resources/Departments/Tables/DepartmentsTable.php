<?php

namespace App\Filament\Resources\Departments\Tables;

use App\Filament\Support\DeleteDependencyGuard;
use App\Models\Department;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DepartmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('short_name')
                    ->label('Short name')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('courses_count')
                    ->label('Courses')
                    ->counts('courses')
                    ->sortable(),
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
                //
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No departments found')
            ->emptyStateDescription('Create your first department to organize courses and questions.')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, Department $record): void {
                        if (! $record->hasDeletionDependencies()) {
                            return;
                        }

                        DeleteDependencyGuard::cancelSingle($action, 'department', 'courses and questions');
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

                            DeleteDependencyGuard::cancelBulk($action, 'departments', 'courses and questions');
                        }),
                ]),
            ]);
    }
}

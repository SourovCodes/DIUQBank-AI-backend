<?php

namespace App\Filament\Resources\Semesters\Tables;

use App\Filament\Support\DeleteDependencyGuard;
use App\Models\Semester;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SemestersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
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
                //
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No semesters found')
            ->emptyStateDescription('Create your first semester to organize questions.')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, Semester $record): void {
                        if (! $record->hasDeletionDependencies()) {
                            return;
                        }

                        DeleteDependencyGuard::cancelSingle($action, 'semester', 'questions');
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

                            DeleteDependencyGuard::cancelBulk($action, 'semesters', 'questions');
                        }),
                ]),
            ]);
    }
}

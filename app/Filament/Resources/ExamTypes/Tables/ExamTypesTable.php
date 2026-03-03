<?php

namespace App\Filament\Resources\ExamTypes\Tables;

use App\Filament\Support\DeleteDependencyGuard;
use App\Models\ExamType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ExamTypesTable
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
                IconColumn::make('requires_section')
                    ->label('Requires section')
                    ->boolean(),
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
                TernaryFilter::make('requires_section')
                    ->label('Requires section'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No exam types found')
            ->emptyStateDescription('Create your first exam type to classify questions.')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, ExamType $record): void {
                        if (! $record->hasDeletionDependencies()) {
                            return;
                        }

                        DeleteDependencyGuard::cancelSingle($action, 'exam type', 'questions');
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

                            DeleteDependencyGuard::cancelBulk($action, 'exam types', 'questions');
                        }),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Departments\RelationManagers;

use App\Filament\Resources\Questions\QuestionResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('course.name')
                    ->label('Course')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('semester.name')
                    ->label('Semester')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('examType.name')
                    ->label('Exam type')
                    ->searchable()
                    ->sortable(),
            ])
            ->recordUrl(fn (Model $record): string => QuestionResource::getUrl('edit', ['record' => $record]))
            ->recordActions([])
            ->headerActions([
                Action::make('create')
                    ->label('Create question')
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => QuestionResource::getUrl('create', [
                        'department_id' => $this->getOwnerRecord()->getKey(),
                    ])),
            ])
            ->toolbarActions([]);
    }
}

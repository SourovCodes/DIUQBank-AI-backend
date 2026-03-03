<?php

namespace App\Filament\Resources\Departments\RelationManagers;

use App\Filament\Resources\Courses\CourseResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CoursesRelationManager extends RelationManager
{
    protected static string $relationship = 'courses';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Course name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('questions_count')
                    ->label('Questions')
                    ->counts('questions')
                    ->sortable(),
            ])
            ->recordUrl(fn (Model $record): string => CourseResource::getUrl('edit', ['record' => $record]))
            ->recordActions([])
            ->headerActions([
                Action::make('create')
                    ->label('Create course')
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => CourseResource::getUrl('create', [
                        'department_id' => $this->getOwnerRecord()->getKey(),
                    ])),
            ])
            ->toolbarActions([]);
    }
}

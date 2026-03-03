<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Submissions\SubmissionResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('question.id')
                    ->label('Question #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('section')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('batch')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('views')
                    ->numeric()
                    ->sortable(),
            ])
            ->recordUrl(fn (Model $record): string => SubmissionResource::getUrl('edit', ['record' => $record]))
            ->recordActions([])
            ->headerActions([
                Action::make('create')
                    ->label('Create submission')
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => SubmissionResource::getUrl('create', [
                        'user_id' => $this->getOwnerRecord()->getKey(),
                    ])),
            ])
            ->toolbarActions([]);
    }
}

<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Question;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public function form(Schema $schema): Schema
    {
        return QuestionResource::form($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        return QuestionResource::infolist($schema);
    }

    public function table(Table $table): Table
    {
        return QuestionResource::table($table)
            ->recordUrl(fn (Question $record): string => QuestionResource::getUrl('edit', ['record' => $record]))
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->url(fn (Question $record): string => QuestionResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}

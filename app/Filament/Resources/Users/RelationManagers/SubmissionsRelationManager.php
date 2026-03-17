<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Submissions\SubmissionResource;
use App\Models\Submission;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    public function form(Schema $schema): Schema
    {
        return SubmissionResource::form($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        return SubmissionResource::infolist($schema);
    }

    public function table(Table $table): Table
    {
        return SubmissionResource::table($table)
            ->recordUrl(fn (Submission $record): string => SubmissionResource::getUrl('edit', ['record' => $record]))
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->url(fn (Submission $record): string => SubmissionResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Departments\RelationManagers;

use App\Filament\Resources\Courses\CourseResource;
use App\Models\Course;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CoursesRelationManager extends RelationManager
{
    protected static string $relationship = 'courses';

    public function form(Schema $schema): Schema
    {
        return CourseResource::form($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        return CourseResource::infolist($schema);
    }

    public function table(Table $table): Table
    {
        return CourseResource::table($table)
            ->recordUrl(fn (Course $record): string => CourseResource::getUrl('edit', ['record' => $record]))
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->url(fn (Course $record): string => CourseResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}

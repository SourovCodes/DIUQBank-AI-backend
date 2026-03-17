<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Models\Question;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuestionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Question Overview')
                    ->schema([
                        TextEntry::make('id')
                            ->label('Question ID')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('department.name')
                            ->label('Department'),
                        TextEntry::make('course.name')
                            ->label('Course'),
                        TextEntry::make('semester.name')
                            ->label('Semester'),
                        TextEntry::make('examType.name')
                            ->label('Exam Type'),
                        TextEntry::make('submissions_count')
                            ->label('Submissions')
                            ->state(fn (Question $record): int => $record->submissions()->count())
                            ->badge()
                            ->color('warning'),
                        TextEntry::make('views')
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}

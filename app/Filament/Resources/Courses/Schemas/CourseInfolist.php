<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Models\Course;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Course Overview')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('department.name')
                            ->label('Department'),
                        TextEntry::make('questions_count')
                            ->label('Questions')
                            ->state(fn (Course $record): int => $record->questions()->count())
                            ->badge()
                            ->color('warning'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}

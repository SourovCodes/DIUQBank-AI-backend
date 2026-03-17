<?php

namespace App\Filament\Resources\Departments\Schemas;

use App\Models\Department;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DepartmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Department Overview')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('short_name')
                            ->label('Short Name')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('courses_count')
                            ->label('Courses')
                            ->state(fn (Department $record): int => $record->courses()->count())
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('questions_count')
                            ->label('Questions')
                            ->state(fn (Department $record): int => $record->questions()->count())
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

<?php

namespace App\Filament\Resources\ExamTypes\Schemas;

use App\Models\ExamType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExamTypeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Exam Type Overview')
                    ->schema([
                        TextEntry::make('name'),
                        IconEntry::make('requires_section')
                            ->label('Requires Section')
                            ->boolean(),
                        TextEntry::make('questions_count')
                            ->label('Questions')
                            ->state(fn (ExamType $record): int => $record->questions()->count())
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

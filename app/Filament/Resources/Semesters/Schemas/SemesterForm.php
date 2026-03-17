<?php

namespace App\Filament\Resources\Semesters\Schemas;

use App\Models\Semester;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SemesterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Semester information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->minLength(2)
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Enter semester name')
                            ->columnSpanFull(),
                    ]),
                Section::make('Timestamps')
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn (?string $operation): bool => $operation === 'edit')
                    ->schema([
                        TextEntry::make('created_at_display')
                            ->label('Created at')
                            ->state(fn (?Semester $record): string => $record?->created_at?->toDayDateTimeString() ?? '—'),
                        TextEntry::make('updated_at_display')
                            ->label('Updated at')
                            ->state(fn (?Semester $record): string => $record?->updated_at?->toDayDateTimeString() ?? '—'),
                    ]),
            ]);
    }
}

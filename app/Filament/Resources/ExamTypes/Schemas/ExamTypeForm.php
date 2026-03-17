<?php

namespace App\Filament\Resources\ExamTypes\Schemas;

use App\Models\ExamType;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExamTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Exam type information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->minLength(2)
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Enter exam type name'),
                        Toggle::make('requires_section')
                            ->label('Requires Section')
                            ->inline(false)
                            ->default(false),
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
                            ->state(fn (?ExamType $record): string => $record?->created_at?->toDayDateTimeString() ?? '—'),
                        TextEntry::make('updated_at_display')
                            ->label('Updated at')
                            ->state(fn (?ExamType $record): string => $record?->updated_at?->toDayDateTimeString() ?? '—'),
                    ]),
            ]);
    }
}

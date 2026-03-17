<?php

namespace App\Filament\Resources\Submissions\Schemas;

use App\Models\Question;
use App\Models\Submission;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Submission information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Uploader')
                            ->relationship('uploader', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Select an uploader'),
                        Select::make('question_id')
                            ->label('Question')
                            ->relationship('question', 'id')
                            ->getOptionLabelFromRecordUsing(fn (Question $record): string => $record->getSubmissionDisplayLabel())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a question'),
                        TextInput::make('section')
                            ->maxLength(255)
                            ->placeholder('Enter section, if applicable'),
                        TextInput::make('batch')
                            ->maxLength(255)
                            ->placeholder('Enter batch, if applicable'),
                    ]),
                Section::make('Storage')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('pdf_path')
                            ->label('Original PDF Path')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('compressed_pdf_path')
                            ->label('Compressed PDF Path')
                            ->disabled()
                            ->dehydrated(false),
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
                            ->state(fn (?Submission $record): string => $record?->created_at?->toDayDateTimeString() ?? '—'),
                        TextEntry::make('updated_at_display')
                            ->label('Updated at')
                            ->state(fn (?Submission $record): string => $record?->updated_at?->toDayDateTimeString() ?? '—'),
                    ]),
            ]);
    }
}

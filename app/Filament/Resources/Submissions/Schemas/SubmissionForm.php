<?php

namespace App\Filament\Resources\Submissions\Schemas;

use App\Models\Submission;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                            ->searchable()
                            ->preload()
                            ->default(fn () => request()->query('user_id'))
                            ->required(),
                        Select::make('question_id')
                            ->label('Question')
                            ->relationship('question', 'id')
                            ->searchable()
                            ->preload()
                            ->default(fn () => request()->query('question_id'))
                            ->required(),
                        TextInput::make('section')
                            ->maxLength(255)
                            ->nullable(),
                        TextInput::make('batch')
                            ->maxLength(255)
                            ->nullable(),
                        TextInput::make('views')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->required(),
                        FileUpload::make('pdf_path')
                            ->label('PDF')
                            ->disk('s3')
                            ->directory('submissions')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->openable()
                            ->required(),
                    ]),
                Section::make('Timestamps')
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn (?string $operation): bool => $operation === 'edit')
                    ->schema([
                        Placeholder::make('created_at_display')
                            ->label('Created at')
                            ->content(fn (?Submission $record): string => $record?->created_at?->toDayDateTimeString() ?? '—'),
                        Placeholder::make('updated_at_display')
                            ->label('Updated at')
                            ->content(fn (?Submission $record): string => $record?->updated_at?->toDayDateTimeString() ?? '—'),
                    ]),
            ]);
    }
}

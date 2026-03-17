<?php

namespace App\Filament\Resources\Submissions\Schemas;

use App\Models\Submission;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Submission Details')
                    ->schema([
                        TextEntry::make('id')
                            ->label('Submission ID')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('uploader.name')
                            ->label('Uploader'),
                        TextEntry::make('question_summary')
                            ->label('Question')
                            ->state(fn (Submission $record): string => $record->question?->getSubmissionDisplayLabel() ?? 'Unknown question'),
                        TextEntry::make('section')
                            ->placeholder('Not provided'),
                        TextEntry::make('batch')
                            ->placeholder('Not provided'),
                        TextEntry::make('views')
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('pdf_size_label')
                            ->label('Original PDF Size')
                            ->state(fn (Submission $record): string => $record->getPdfSizeLabel() ?? 'Unavailable'),
                        TextEntry::make('compressed_pdf_size_label')
                            ->label('Compressed PDF Size')
                            ->state(fn (Submission $record): string => $record->getCompressedPdfSizeLabel() ?? 'Not generated'),
                        TextEntry::make('pdf_path')
                            ->label('Original PDF Path')
                            ->copyable(),
                        TextEntry::make('compressed_pdf_path')
                            ->label('Compressed PDF Path')
                            ->placeholder('Not generated')
                            ->copyable(),
                        TextEntry::make('pdf_url')
                            ->label('PDF')
                            ->state(fn (Submission $record): string => $record->getPdfUrl() ? 'Open current PDF' : 'Unavailable')
                            ->url(fn (Submission $record): ?string => $record->getPdfUrl(), shouldOpenInNewTab: true)
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}

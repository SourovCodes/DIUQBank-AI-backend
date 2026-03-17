<?php

namespace App\Filament\Resources\QuickUploads\Schemas;

use App\Enums\QuickUploadStatus;
use App\Models\QuickUpload;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuickUploadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quick Upload Details')
                    ->schema([
                        TextEntry::make('id')
                            ->label('Upload ID')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('uploader.name')
                            ->label('Uploader'),
                        TextEntry::make('reviewer.name')
                            ->label('Reviewer')
                            ->placeholder('Not assigned'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(static fn (QuickUploadStatus $state): string => $state->label())
                            ->color(static fn (QuickUploadStatus $state): string => $state->color()),
                        TextEntry::make('reason')
                            ->label('Review Notes')
                            ->placeholder('No notes yet')
                            ->columnSpanFull(),
                        TextEntry::make('pdf_size_label')
                            ->label('Original PDF Size')
                            ->state(fn (QuickUpload $record): string => $record->getPdfSizeLabel() ?? 'Unavailable'),
                        TextEntry::make('compressed_pdf_size_label')
                            ->label('Compressed PDF Size')
                            ->state(fn (QuickUpload $record): string => $record->getCompressedPdfSizeLabel() ?? 'Not generated'),
                        TextEntry::make('pdf_path')
                            ->label('Original PDF Path')
                            ->copyable(),
                        TextEntry::make('compressed_pdf_path')
                            ->label('Compressed PDF Path')
                            ->placeholder('Not generated')
                            ->copyable(),
                        TextEntry::make('original_pdf_url')
                            ->label('Original PDF')
                            ->state(fn (QuickUpload $record): string => $record->getOriginalPdfUrl() ? 'Open original PDF' : 'Unavailable')
                            ->url(fn (QuickUpload $record): ?string => $record->getOriginalPdfUrl(), shouldOpenInNewTab: true)
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('compressed_pdf_url')
                            ->label('Compressed PDF')
                            ->state(fn (QuickUpload $record): string => $record->getCompressedPdfUrl() ? 'Open compressed PDF' : 'Not generated')
                            ->url(fn (QuickUpload $record): ?string => $record->getCompressedPdfUrl(), shouldOpenInNewTab: true)
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('ai_processed_at')
                            ->dateTime()
                            ->placeholder('Not processed'),
                        TextEntry::make('manual_review_requested_at')
                            ->dateTime()
                            ->placeholder('Not requested'),
                        TextEntry::make('manual_reviewed_at')
                            ->dateTime()
                            ->placeholder('Not reviewed'),
                    ])
                    ->columns(2),
            ]);
    }
}

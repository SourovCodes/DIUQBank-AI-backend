<?php

namespace App\Filament\Resources\QuickUploads\Schemas;

use App\Enums\QuickUploadStatus;
use App\Models\QuickUpload;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuickUploadForm
{
    public static function configure(Schema $schema): Schema
    {
        $statusOptions = collect(QuickUploadStatus::cases())
            ->mapWithKeys(fn (QuickUploadStatus $status): array => [$status->value => $status->label()])
            ->all();

        return $schema
            ->components([
                Section::make('Upload information')
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
                        Select::make('reviewer_id')
                            ->label('Reviewer')
                            ->relationship('reviewer', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a reviewer'),
                        Select::make('status')
                            ->options($statusOptions)
                            ->required()
                            ->placeholder('Select a status'),
                        Textarea::make('reason')
                            ->label('Review Notes')
                            ->rows(4)
                            ->placeholder('Add any review notes'),
                    ]),
                Section::make('Storage')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('original_pdf_link')
                            ->label('Original PDF')
                            ->state(fn (?QuickUpload $record): string => $record?->getOriginalPdfUrl() ? 'Open original PDF' : 'Unavailable')
                            ->url(fn (?QuickUpload $record): ?string => $record?->getOriginalPdfUrl(), shouldOpenInNewTab: true)
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('pdf_size_display')
                            ->label('Original PDF Size')
                            ->state(fn (?QuickUpload $record): string => $record?->getPdfSizeLabel() ?? 'Unavailable'),
                        TextEntry::make('compressed_pdf_link')
                            ->label('Compressed PDF')
                            ->state(fn (?QuickUpload $record): string => $record?->getCompressedPdfUrl() ? 'Open compressed PDF' : 'Not generated')
                            ->url(fn (?QuickUpload $record): ?string => $record?->getCompressedPdfUrl(), shouldOpenInNewTab: true)
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('compressed_pdf_size_display')
                            ->label('Compressed PDF Size')
                            ->state(fn (?QuickUpload $record): string => $record?->getCompressedPdfSizeLabel() ?? 'Not generated'),
                        TextInput::make('pdf_path')
                            ->label('Original PDF Path')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('compressed_pdf_path')
                            ->label('Compressed PDF Path')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
                Section::make('Processing Timeline')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        DateTimePicker::make('ai_processed_at'),
                        DateTimePicker::make('manual_review_requested_at'),
                        DateTimePicker::make('manual_reviewed_at'),
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
                            ->state(fn (?QuickUpload $record): string => $record?->created_at?->toDayDateTimeString() ?? '—'),
                        TextEntry::make('updated_at_display')
                            ->label('Updated at')
                            ->state(fn (?QuickUpload $record): string => $record?->updated_at?->toDayDateTimeString() ?? '—'),
                    ]),
            ]);
    }
}

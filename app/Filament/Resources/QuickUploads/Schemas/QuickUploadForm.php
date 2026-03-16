<?php

namespace App\Filament\Resources\QuickUploads\Schemas;

use App\Enums\QuickUploadStatus;
use App\Models\QuickUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class QuickUploadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quick upload')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Uploader')
                            ->relationship('uploader', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('status')
                            ->options(collect(QuickUploadStatus::cases())
                                ->mapWithKeys(fn (QuickUploadStatus $status): array => [$status->value => $status->label()])
                                ->all())
                            ->default(QuickUploadStatus::Pending->value)
                            ->required()
                            ->live(),
                        FileUpload::make('pdf_path')
                            ->label('PDF')
                            ->disk('s3')
                            ->directory('quick-uploads')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->openable()
                            ->required(),
                        Textarea::make('reason')
                            ->label('Reason')
                            ->rows(4)
                            ->visible(fn (Get $get): bool => in_array($get('status'), [
                                QuickUploadStatus::AiRejected->value,
                                QuickUploadStatus::ManualReviewRequested->value,
                                QuickUploadStatus::ManualApproved->value,
                                QuickUploadStatus::ManualRejected->value,
                            ], true))
                            ->required(fn (Get $get): bool => in_array($get('status'), [
                                QuickUploadStatus::AiRejected->value,
                                QuickUploadStatus::ManualRejected->value,
                            ], true)),
                    ]),
                Section::make('PDF files')
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Placeholder::make('pdf_size_display')
                            ->label('Original size')
                            ->content(fn (?QuickUpload $record): string => $record?->getPdfSizeLabel() ?? '—'),
                        Placeholder::make('compressed_pdf_size_display')
                            ->label('Compressed size')
                            ->content(fn (?QuickUpload $record): string => $record?->getCompressedPdfSizeLabel() ?? '—'),
                        Placeholder::make('compressed_pdf_path_display')
                            ->label('Compressed PDF path')
                            ->content(fn (?QuickUpload $record): string => $record?->compressed_pdf_path ?? '—')
                            ->columnSpanFull(),
                    ]),
                Section::make('Workflow timeline')
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Placeholder::make('reviewer_display')
                            ->label('Reviewer')
                            ->content(fn (?QuickUpload $record): string => $record?->reviewer?->name ?? '—'),
                        Placeholder::make('created_at_display')
                            ->label('Created at')
                            ->content(fn (?QuickUpload $record): string => $record?->created_at?->toDayDateTimeString() ?? '—'),
                        Placeholder::make('updated_at_display')
                            ->label('Updated at')
                            ->content(fn (?QuickUpload $record): string => $record?->updated_at?->toDayDateTimeString() ?? '—'),
                        Placeholder::make('ai_processed_at_display')
                            ->label('AI processed at')
                            ->content(fn (?QuickUpload $record): string => $record?->ai_processed_at?->toDayDateTimeString() ?? '—'),
                        Placeholder::make('manual_review_requested_at_display')
                            ->label('Manual review requested at')
                            ->content(fn (?QuickUpload $record): string => $record?->manual_review_requested_at?->toDayDateTimeString() ?? '—'),
                        Placeholder::make('manual_reviewed_at_display')
                            ->label('Manual reviewed at')
                            ->content(fn (?QuickUpload $record): string => $record?->manual_reviewed_at?->toDayDateTimeString() ?? '—'),
                    ]),
                Section::make('PDF preview')
                    ->columnSpanFull()
                    ->visible(fn (?string $operation, ?QuickUpload $record): bool => $operation === 'edit' && filled($record?->pdf_path))
                    ->schema([
                        View::make('filament.resources.quick-uploads.components.pdf-viewer')
                            ->columnSpanFull()
                            ->viewData(function (?QuickUpload $record): array {
                                if (! $record instanceof QuickUpload) {
                                    return ['pdfUrl' => null];
                                }

                                return ['pdfUrl' => $record->getPdfUrl()];
                            }),
                    ]),
            ]);
    }
}

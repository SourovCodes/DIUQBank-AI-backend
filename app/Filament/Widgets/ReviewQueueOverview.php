<?php

namespace App\Filament\Widgets;

use App\Enums\QuickUploadStatus;
use App\Filament\Resources\QuickUploads\QuickUploadResource;
use App\Models\QuickUpload;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ReviewQueueOverview extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Quick Upload Review Queue';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => QuickUpload::query()
                ->with(['uploader', 'reviewer'])
                ->whereIn('status', [
                    QuickUploadStatus::Pending,
                    QuickUploadStatus::Processing,
                    QuickUploadStatus::AiRejected,
                    QuickUploadStatus::ManualReviewRequested,
                ])
                ->latest())
            ->columns([
                TextColumn::make('id')
                    ->label('Upload')
                    ->sortable(),
                TextColumn::make('uploader.name')
                    ->label('Uploader')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(static fn (QuickUploadStatus $state): string => $state->label())
                    ->color(static fn (QuickUploadStatus $state): string => $state->color()),
                TextColumn::make('reason')
                    ->label('Review Notes')
                    ->limit(40)
                    ->placeholder('No notes yet'),
                TextColumn::make('pdf_size')
                    ->label('Original PDF Size')
                    ->state(fn (QuickUpload $record): string => $record->getPdfSizeLabel() ?? 'Unavailable'),
                TextColumn::make('compressed_pdf_size')
                    ->label('Compressed PDF Size')
                    ->state(fn (QuickUpload $record): string => $record->getCompressedPdfSizeLabel() ?? 'Not generated'),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
            ])
            ->recordUrl(fn (QuickUpload $record): string => QuickUploadResource::getUrl('edit', ['record' => $record]))
            ->recordActions([
                Action::make('originalPdf')
                    ->label('Original PDF')
                    ->color('gray')
                    ->url(fn (QuickUpload $record): ?string => $record->getOriginalPdfUrl(), shouldOpenInNewTab: true)
                    ->visible(fn (QuickUpload $record): bool => filled($record->getOriginalPdfUrl())),
                Action::make('compressedPdf')
                    ->label('Compressed PDF')
                    ->color('gray')
                    ->url(fn (QuickUpload $record): ?string => $record->getCompressedPdfUrl(), shouldOpenInNewTab: true)
                    ->visible(fn (QuickUpload $record): bool => filled($record->getCompressedPdfUrl())),
                Action::make('open')
                    ->label('Edit')
                    ->url(fn (QuickUpload $record): string => QuickUploadResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated([5, 10, 25]);
    }
}

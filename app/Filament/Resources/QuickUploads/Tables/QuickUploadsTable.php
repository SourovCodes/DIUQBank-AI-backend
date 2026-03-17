<?php

namespace App\Filament\Resources\QuickUploads\Tables;

use App\Enums\QuickUploadStatus;
use App\Filament\Resources\QuickUploads\QuickUploadResource;
use App\Models\QuickUpload;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuickUploadsTable
{
    public static function configure(Table $table): Table
    {
        $statusOptions = collect(QuickUploadStatus::cases())
            ->mapWithKeys(fn (QuickUploadStatus $status): array => [$status->value => $status->label()])
            ->all();

        return $table
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (QuickUpload $record): string => QuickUploadResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('id')
                    ->label('Upload')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('uploader.name')
                    ->label('Uploader')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(static fn (QuickUploadStatus $state): string => $state->label())
                    ->color(static fn (QuickUploadStatus $state): string => $state->color())
                    ->sortable(),
                TextColumn::make('reviewer.name')
                    ->label('Reviewer')
                    ->placeholder('Unassigned')
                    ->toggleable(),
                TextColumn::make('reason')
                    ->label('Review Notes')
                    ->placeholder('No notes yet')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('pdf_size')
                    ->label('Original PDF Size')
                    ->state(fn (QuickUpload $record): string => $record->getPdfSizeLabel() ?? 'Unavailable')
                    ->toggleable(),
                TextColumn::make('compressed_pdf_size')
                    ->label('Compressed PDF Size')
                    ->state(fn (QuickUpload $record): string => $record->getCompressedPdfSizeLabel() ?? 'Not generated')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('manual_reviewed_at')
                    ->label('Reviewed')
                    ->since()
                    ->placeholder('Pending')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options($statusOptions),
                SelectFilter::make('user_id')
                    ->label('Uploader')
                    ->options(User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                SelectFilter::make('reviewer_id')
                    ->label('Reviewer')
                    ->options(User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
            ])
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
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}

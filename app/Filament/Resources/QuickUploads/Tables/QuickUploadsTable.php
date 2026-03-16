<?php

namespace App\Filament\Resources\QuickUploads\Tables;

use App\Enums\QuickUploadStatus;
use App\Jobs\CompressQuickUploadPdf;
use App\Models\QuickUpload;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class QuickUploadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('uploader.name')
                    ->label('Uploader')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (QuickUploadStatus|string $state): string => ($state instanceof QuickUploadStatus ? $state : QuickUploadStatus::from($state))->label())
                    ->color(fn (QuickUploadStatus|string $state): string => ($state instanceof QuickUploadStatus ? $state : QuickUploadStatus::from($state))->color())
                    ->sortable(),
                TextColumn::make('reviewer.name')
                    ->label('Reviewer')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(40)
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('pdf_size_display')
                    ->label('Original size')
                    ->state(fn (QuickUpload $record): string => $record->getPdfSizeLabel() ?? '—')
                    ->toggleable(),
                TextColumn::make('compressed_pdf_size_display')
                    ->label('Compressed size')
                    ->state(fn (QuickUpload $record): string => $record->getCompressedPdfSizeLabel() ?? '—')
                    ->toggleable(),
                TextColumn::make('pdf_open')
                    ->label('PDF')
                    ->state('Open PDF')
                    ->url(fn (QuickUpload $record): ?string => $record->getPdfUrl())
                    ->openUrlInNewTab(),
                TextColumn::make('manual_review_requested_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('manual_reviewed_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(QuickUploadStatus::cases())
                        ->mapWithKeys(fn (QuickUploadStatus $status): array => [$status->value => $status->label()])
                        ->all()),
                SelectFilter::make('user_id')
                    ->label('Uploader')
                    ->relationship('uploader', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('reviewer_id')
                    ->label('Reviewer')
                    ->relationship('reviewer', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No quick uploads found')
            ->emptyStateDescription('Uploads will appear here for AI and admin review.')
            ->recordActions([
                Action::make('queueCompression')
                    ->label(fn (QuickUpload $record): string => filled($record->compressed_pdf_path) ? 'Recompress PDF' : 'Compress PDF')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->disabled(fn (QuickUpload $record): bool => blank($record->pdf_path))
                    ->action(function (QuickUpload $record): void {
                        CompressQuickUploadPdf::dispatch($record);

                        Notification::make()
                            ->title('Quick upload queued')
                            ->body('PDF compression has been queued.')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('queueCompression')
                        ->label('Queue Compression')
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (EloquentCollection $records): void {
                            foreach ($records as $record) {
                                if ($record instanceof QuickUpload && filled($record->pdf_path)) {
                                    CompressQuickUploadPdf::dispatch($record);
                                }
                            }

                            Notification::make()
                                ->title('Quick uploads queued')
                                ->body($records->count().' quick upload(s) queued for PDF compression.')
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\QuickUploads\Tables;

use App\Enums\QuickUploadStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

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
                TextColumn::make('ai_rejection_reason')
                    ->label('AI reason')
                    ->limit(40)
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('manual_rejection_reason')
                    ->label('Manual reason')
                    ->limit(40)
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('pdf_open')
                    ->label('PDF')
                    ->state('Open PDF')
                    ->url(function ($record): ?string {
                        if (blank($record->pdf_path)) {
                            return null;
                        }

                        /** @var FilesystemAdapter $disk */
                        $disk = Storage::disk('s3');

                        try {
                            return $disk->temporaryUrl($record->pdf_path, now()->addMinutes(10));
                        } catch (Throwable) {
                            return $disk->url($record->pdf_path);
                        }
                    })
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

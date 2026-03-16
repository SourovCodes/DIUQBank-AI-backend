<?php

namespace App\Filament\Resources\Submissions\Tables;

use App\Jobs\CompressSubmissionPdf;
use App\Models\Question;
use App\Models\Submission;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class SubmissionsTable
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
                TextColumn::make('question_display')
                    ->label('Question')
                    ->state(fn ($record): string => $record->question?->getSubmissionDisplayLabel() ?? 'N/A')
                    ->searchable([
                        'question.id',
                        'question.department.short_name',
                        'question.course.name',
                        'question.semester.name',
                        'question.examType.name',
                    ])
                    ->toggleable(),
                TextColumn::make('section')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('batch')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('views')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pdf_size_display')
                    ->label('Original size')
                    ->state(fn (Submission $record): string => $record->getPdfSizeLabel() ?? '—')
                    ->toggleable(),
                TextColumn::make('compressed_pdf_size_display')
                    ->label('Compressed size')
                    ->state(fn (Submission $record): string => $record->getCompressedPdfSizeLabel() ?? '—')
                    ->toggleable(),
                TextColumn::make('pdf_open')
                    ->label('PDF')
                    ->state('Open PDF')
                    ->url(fn (Submission $record): ?string => $record->getPdfUrl())
                    ->openUrlInNewTab(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Uploader')
                    ->relationship('uploader', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('question_id')
                    ->label('Question')
                    ->relationship(
                        'question',
                        'id',
                        fn (Builder $query): Builder => $query->with([
                            'department:id,short_name',
                            'course:id,name',
                            'semester:id,name',
                            'examType:id,name',
                        ])
                    )
                    ->getOptionLabelFromRecordUsing(fn (Question $record): string => $record->getSubmissionDisplayLabel())
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No submissions found')
            ->emptyStateDescription('Create your first submission with an uploader, question, and PDF.')
            ->recordActions([
                Action::make('queueCompression')
                    ->label(fn (Submission $record): string => filled($record->compressed_pdf_path) ? 'Recompress PDF' : 'Compress PDF')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->disabled(fn (Submission $record): bool => blank($record->pdf_path))
                    ->action(function (Submission $record): void {
                        CompressSubmissionPdf::dispatch($record);

                        Notification::make()
                            ->title('Submission queued')
                            ->body('PDF compression has been queued.')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
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
                                if ($record instanceof Submission && filled($record->pdf_path)) {
                                    CompressSubmissionPdf::dispatch($record);
                                }
                            }

                            Notification::make()
                                ->title('Submissions queued')
                                ->body($records->count().' submission(s) queued for PDF compression.')
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

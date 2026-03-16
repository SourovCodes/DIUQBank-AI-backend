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
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

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
                    ->label('Queue Compression')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function (Submission $record): void {
                        CompressSubmissionPdf::dispatch($record);

                        Notification::make()
                            ->title('Submission queued')
                            ->body('PDF compression has been queued for background processing.')
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
                                if ($record instanceof Submission) {
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

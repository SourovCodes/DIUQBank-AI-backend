<?php

namespace App\Filament\Resources\QuickUploads\Tables;

use App\Enums\QuickUploadStatus;
use App\Filament\Resources\QuickUploads\QuickUploadResource;
use App\Models\QuickUpload;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
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
                Action::make('openPdf')
                    ->label('PDF')
                    ->color('gray')
                    ->url(fn (QuickUpload $record): ?string => $record->getPdfUrl(), shouldOpenInNewTab: true)
                    ->visible(fn (QuickUpload $record): bool => filled($record->getPdfUrl())),
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (QuickUpload $record): bool => $record->status !== QuickUploadStatus::ManualApproved)
                    ->action(function (QuickUpload $record): void {
                        $record->forceFill([
                            'status' => QuickUploadStatus::ManualApproved,
                            'reviewer_id' => auth()->id(),
                            'manual_reviewed_at' => now(),
                            'reason' => null,
                        ])->save();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->fillForm(fn (QuickUpload $record): array => ['reason' => $record->reason])
                    ->schema([
                        Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (QuickUpload $record, array $data): void {
                        $record->forceFill([
                            'status' => QuickUploadStatus::ManualRejected,
                            'reviewer_id' => auth()->id(),
                            'manual_reviewed_at' => now(),
                            'reason' => $data['reason'],
                        ])->save();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}

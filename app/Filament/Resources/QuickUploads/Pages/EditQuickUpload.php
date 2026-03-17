<?php

namespace App\Filament\Resources\QuickUploads\Pages;

use App\Enums\QuickUploadStatus;
use App\Filament\Resources\QuickUploads\QuickUploadResource;
use App\Filament\Support\GuardedDeleteAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;

class EditQuickUpload extends EditRecord
{
    protected static string $resource = QuickUploadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openPdf')
                ->label('Open PDF')
                ->color('gray')
                ->url(fn (): ?string => $this->record->getPdfUrl(), shouldOpenInNewTab: true)
                ->visible(fn (): bool => filled($this->record->getPdfUrl())),
            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status !== QuickUploadStatus::ManualApproved)
                ->action(function (): void {
                    $this->record->forceFill([
                        'status' => QuickUploadStatus::ManualApproved,
                        'reviewer_id' => auth()->id(),
                        'manual_reviewed_at' => now(),
                        'reason' => null,
                    ])->save();
                }),
            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->fillForm(fn (): array => ['reason' => $this->record->reason])
                ->schema([
                    Textarea::make('reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data): void {
                    $this->record->forceFill([
                        'status' => QuickUploadStatus::ManualRejected,
                        'reviewer_id' => auth()->id(),
                        'manual_reviewed_at' => now(),
                        'reason' => $data['reason'],
                    ])->save();
                }),
            GuardedDeleteAction::make(),
        ];
    }
}

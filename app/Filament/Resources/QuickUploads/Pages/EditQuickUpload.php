<?php

namespace App\Filament\Resources\QuickUploads\Pages;

use App\Filament\Resources\QuickUploads\QuickUploadResource;
use App\Filament\Support\GuardedDeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditQuickUpload extends EditRecord
{
    protected static string $resource = QuickUploadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openOriginalPdf')
                ->label('Open Original PDF')
                ->color('gray')
                ->url(fn (): ?string => $this->record->getOriginalPdfUrl(), shouldOpenInNewTab: true)
                ->visible(fn (): bool => filled($this->record->getOriginalPdfUrl())),
            Action::make('openCompressedPdf')
                ->label('Open Compressed PDF')
                ->color('gray')
                ->url(fn (): ?string => $this->record->getCompressedPdfUrl(), shouldOpenInNewTab: true)
                ->visible(fn (): bool => filled($this->record->getCompressedPdfUrl())),
            GuardedDeleteAction::make(),
        ];
    }
}

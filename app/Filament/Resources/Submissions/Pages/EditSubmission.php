<?php

namespace App\Filament\Resources\Submissions\Pages;

use App\Filament\Resources\Submissions\SubmissionResource;
use App\Filament\Support\GuardedDeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditSubmission extends EditRecord
{
    protected static string $resource = SubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openPdf')
                ->label('Open PDF')
                ->color('gray')
                ->url(fn (): ?string => $this->record->getPdfUrl(), shouldOpenInNewTab: true)
                ->visible(fn (): bool => filled($this->record->getPdfUrl())),
            GuardedDeleteAction::make(),
        ];
    }
}

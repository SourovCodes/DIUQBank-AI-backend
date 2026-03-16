<?php

namespace App\Filament\Resources\QuickUploads\Pages;

use App\Filament\Resources\QuickUploads\QuickUploadResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuickUpload extends EditRecord
{
    protected static string $resource = QuickUploadResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return QuickUploadResource::mutateWorkflowData($data, $this->getRecord());
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\QuickUploads\Pages;

use App\Filament\Resources\QuickUploads\QuickUploadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuickUpload extends CreateRecord
{
    protected static string $resource = QuickUploadResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return QuickUploadResource::mutateWorkflowData($data);
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuickUploadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'reason' => $this->reason,
            'pdf_path' => $this->pdf_path,
            'pdf_size' => $this->pdf_size,
            'compressed_pdf_path' => $this->compressed_pdf_path,
            'compressed_pdf_size' => $this->compressed_pdf_size,
            'ai_processed_at' => $this->ai_processed_at?->toISOString(),
            'manual_review_requested_at' => $this->manual_review_requested_at?->toISOString(),
            'manual_reviewed_at' => $this->manual_reviewed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

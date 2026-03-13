<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
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
            'section' => $this->section,
            'batch' => $this->batch,
            'views' => $this->views,
            'pdf_url' => $this->getPdfUrl(),
            'uploader' => $this->whenLoaded('uploader', fn (): array => [
                'id' => $this->uploader->id,
                'username' => $this->uploader->username,
                'name' => $this->uploader->name,
                'avatar' => $this->uploader->avatar,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

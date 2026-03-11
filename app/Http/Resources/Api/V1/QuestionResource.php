<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
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
            'display_label' => $this->getSubmissionDisplayLabel(),
            'views' => $this->views,
            'submissions_count' => $this->whenCounted('submissions'),
            'department' => $this->whenLoaded('department', fn (): array => [
                'id' => $this->department->id,
                'name' => $this->department->name,
                'short_name' => $this->department->short_name,
            ]),
            'course' => $this->whenLoaded('course', fn (): array => [
                'id' => $this->course->id,
                'department_id' => $this->course->department_id,
                'name' => $this->course->name,
            ]),
            'semester' => $this->whenLoaded('semester', fn (): array => [
                'id' => $this->semester->id,
                'name' => $this->semester->name,
            ]),
            'exam_type' => $this->whenLoaded('examType', fn (): array => [
                'id' => $this->examType->id,
                'name' => $this->examType->name,
            ]),
            'submissions' => SubmissionResource::collection($this->whenLoaded('submissions')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

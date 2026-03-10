<?php

namespace Database\Factories;

use App\Enums\QuickUploadStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuickUpload>
 */
class QuickUploadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'pdf_path' => 'quick-uploads/'.fake()->uuid().'.pdf',
            'status' => QuickUploadStatus::Pending,
            'ai_rejection_reason' => null,
            'ai_processed_at' => null,
            'manual_review_requested_at' => null,
            'reviewer_id' => null,
            'manual_rejection_reason' => null,
            'manual_reviewed_at' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (): array => [
            'status' => QuickUploadStatus::Processing,
        ]);
    }

    public function aiRejected(?string $reason = null): static
    {
        return $this->state(fn (): array => [
            'status' => QuickUploadStatus::AiRejected,
            'ai_rejection_reason' => $reason ?? fake()->sentence(),
            'ai_processed_at' => now(),
        ]);
    }

    public function manualReviewRequested(): static
    {
        return $this->state(fn (): array => [
            'status' => QuickUploadStatus::ManualReviewRequested,
            'manual_review_requested_at' => now(),
        ]);
    }

    public function manualRejected(?User $reviewer = null, ?string $reason = null): static
    {
        return $this->state(fn (): array => [
            'status' => QuickUploadStatus::ManualRejected,
            'manual_review_requested_at' => now()->subHour(),
            'reviewer_id' => $reviewer?->getKey() ?? User::factory(),
            'manual_rejection_reason' => $reason ?? fake()->sentence(),
            'manual_reviewed_at' => now(),
        ]);
    }
}

<?php

use App\Enums\QuickUploadStatus;
use App\Models\QuickUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('casts the quick upload status to the enum', function (): void {
    $quickUpload = QuickUpload::factory()->manualReviewRequested()->create();

    expect($quickUpload->status)
        ->toBe(QuickUploadStatus::ManualReviewRequested)
        ->and($quickUpload->uploader)
        ->toBeInstanceOf(User::class)
        ->and($quickUpload->manual_review_requested_at)
        ->not->toBeNull();
});

it('stores manual rejection metadata for admin review outcomes', function (): void {
    $reviewer = User::factory()->create();
    $quickUpload = QuickUpload::factory()->manualRejected($reviewer, 'Document quality is too poor to verify.')->create();

    assertDatabaseHas('quick_uploads', [
        'id' => $quickUpload->id,
        'status' => QuickUploadStatus::ManualRejected->value,
        'reviewer_id' => $reviewer->id,
        'manual_rejection_reason' => 'Document quality is too poor to verify.',
    ]);
});

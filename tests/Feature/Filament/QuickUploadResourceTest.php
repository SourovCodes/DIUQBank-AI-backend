<?php

use App\Enums\QuickUploadStatus;
use App\Filament\Resources\QuickUploads\Pages\CreateQuickUpload;
use App\Filament\Resources\QuickUploads\Pages\EditQuickUpload;
use App\Models\QuickUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    actingAs(User::factory()->create());
});

it('can create a quick upload from the filament resource', function (): void {
    $uploader = User::factory()->create();

    Livewire::test(CreateQuickUpload::class)
        ->set('data.user_id', $uploader->getKey())
        ->set('data.status', QuickUploadStatus::Pending->value)
        ->set('data.pdf_path', ['quick-uploads/test-upload.pdf'])
        ->call('create')
        ->assertHasNoErrors()
        ->assertNotified()
        ->assertRedirect();

    $quickUpload = QuickUpload::query()->latest('id')->first();

    expect($quickUpload)
        ->not->toBeNull()
        ->and($quickUpload->user_id)
        ->toBe($uploader->getKey())
        ->and($quickUpload->status)
        ->toBe(QuickUploadStatus::Pending)
        ->and($quickUpload->pdf_path)
        ->toBe('quick-uploads/test-upload.pdf');

    assertDatabaseHas('quick_uploads', [
        'user_id' => $uploader->getKey(),
        'status' => QuickUploadStatus::Pending->value,
        'pdf_path' => 'quick-uploads/test-upload.pdf',
    ]);
});

it('records reviewer metadata when an admin manually rejects a quick upload', function (): void {
    $quickUpload = QuickUpload::factory()->manualReviewRequested()->create();

    Livewire::test(EditQuickUpload::class, [
        'record' => $quickUpload->getKey(),
    ])
        ->set('data.pdf_path', [$quickUpload->pdf_path])
        ->set('data.status', QuickUploadStatus::ManualRejected->value)
        ->set('data.reason', 'Needs a clearer scan.')
        ->call('save')
        ->assertHasNoErrors();

    $quickUpload->refresh();

    expect($quickUpload->status)
        ->toBe(QuickUploadStatus::ManualRejected)
        ->and($quickUpload->reviewer_id)
        ->toBe(auth()->id())
        ->and($quickUpload->manual_reviewed_at)
        ->not->toBeNull();

    assertDatabaseHas('quick_uploads', [
        'id' => $quickUpload->id,
        'status' => QuickUploadStatus::ManualRejected->value,
        'reviewer_id' => auth()->id(),
        'reason' => 'Needs a clearer scan.',
    ]);
});

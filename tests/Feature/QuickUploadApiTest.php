<?php

use App\Enums\QuickUploadStatus;
use App\Jobs\CompressQuickUploadPdf;
use App\Models\QuickUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('uploads a pdf and creates a quick upload record', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    Queue::fake();
    Storage::fake('s3');

    $pdf = UploadedFile::fake()->create('midterm.pdf', 256, 'application/pdf');

    $response = $this->post('/api/v1/quick-uploads', [
        'pdf' => $pdf,
    ], [
        'Accept' => 'application/json',
    ]);

    $quickUpload = QuickUpload::query()->sole();

    $response
        ->assertCreated()
        ->assertJsonPath('data.id', $quickUpload->id)
        ->assertJsonPath('data.status', QuickUploadStatus::Pending->value)
        ->assertJsonPath('data.reason', null)
        ->assertJsonPath('data.pdf_path', $quickUpload->pdf_path)
        ->assertJsonPath('data.pdf_size', $pdf->getSize());

    expect($quickUpload->pdf_path)->toStartWith('quick-uploads/'.$user->id.'/')
        ->and($quickUpload->pdf_path)->toEndWith('.pdf');

    Storage::disk('s3')->assertExists($quickUpload->pdf_path);

    $this->assertDatabaseHas('quick_uploads', [
        'id' => $quickUpload->id,
        'user_id' => $user->id,
        'pdf_path' => $quickUpload->pdf_path,
        'pdf_size' => $pdf->getSize(),
        'status' => QuickUploadStatus::Pending->value,
    ]);

    Queue::assertPushed(CompressQuickUploadPdf::class, function (CompressQuickUploadPdf $job) use ($quickUpload): bool {
        return $job->quickUpload->is($quickUpload);
    });
});

it('requires authentication to upload a quick upload pdf', function () {
    $this->post('/api/v1/quick-uploads', [], [
        'Accept' => 'application/json',
    ])->assertUnauthorized();
});

it('validates that quick uploads are pdf files within the allowed size', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->post('/api/v1/quick-uploads', [
        'pdf' => UploadedFile::fake()->create('midterm.png', 10_241, 'image/png'),
    ], [
        'Accept' => 'application/json',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['pdf']);
});

it('returns paginated quick uploads for the authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $reviewer = User::factory()->create();

    Sanctum::actingAs($user);

    $oldestUpload = QuickUpload::factory()->create([
        'user_id' => $user->id,
        'pdf_path' => 'quick-uploads/'.$user->id.'/oldest.pdf',
        'created_at' => now()->subDays(2),
    ]);

    $middleUpload = QuickUpload::factory()->manualReviewRequested()->create([
        'user_id' => $user->id,
        'pdf_path' => 'quick-uploads/'.$user->id.'/middle.pdf',
        'created_at' => now()->subDay(),
    ]);

    $latestUpload = QuickUpload::factory()->manualRejected($reviewer, 'Document quality is too poor to verify.')->create([
        'user_id' => $user->id,
        'pdf_path' => 'quick-uploads/'.$user->id.'/latest.pdf',
        'created_at' => now(),
    ]);

    $otherUsersUpload = QuickUpload::factory()->create([
        'user_id' => $otherUser->id,
        'created_at' => now()->addMinute(),
    ]);

    $response = $this->getJson('/api/v1/quick-uploads?per_page=2');

    $response
        ->assertSuccessful()
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $latestUpload->id)
        ->assertJsonPath('data.0.status', QuickUploadStatus::ManualRejected->value)
        ->assertJsonPath('data.0.reason', 'Document quality is too poor to verify.')
        ->assertJsonPath('data.1.id', $middleUpload->id)
        ->assertJsonPath('data.1.status', QuickUploadStatus::ManualReviewRequested->value)
        ->assertJsonMissing(['id' => $oldestUpload->id])
        ->assertJsonMissing(['id' => $otherUsersUpload->id]);
});

it('requires authentication to list quick uploads', function () {
    $this->getJson('/api/v1/quick-uploads')->assertUnauthorized();
});

it('validates quick upload pagination parameters', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/quick-uploads?per_page=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);
});

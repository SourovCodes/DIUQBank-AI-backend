<?php

use App\Enums\QuickUploadStatus;
use App\Models\QuickUpload;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns a presigned upload target without creating a quick upload record', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $disk = \Mockery::mock(FilesystemAdapter::class);

    Storage::shouldReceive('disk')->once()->with('s3')->andReturn($disk);

    $disk->shouldReceive('temporaryUploadUrl')
        ->once()
        ->withArgs(function (string $path, $expiration, array $options): bool {
            expect($path)->toStartWith('quick-uploads/');
            expect($path)->toEndWith('.pdf');
            expect($options)->toBe([
                'ContentType' => 'application/pdf',
            ]);

            return true;
        })
        ->andReturn([
            'url' => 'https://s3.example.com/presigned-upload',
            'headers' => [
                'Content-Type' => 'application/pdf',
            ],
        ]);

    $response = $this->postJson('/api/v1/quick-uploads/upload-url', [
        'file_name' => 'midterm.pdf',
        'content_type' => 'application/pdf',
        'file_size' => 2048,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.upload.method', 'PUT')
        ->assertJsonPath('data.upload.url', 'https://s3.example.com/presigned-upload')
        ->assertJsonPath('data.upload.content_type', 'application/pdf')
        ->assertJsonPath('data.upload.file_name', 'midterm.pdf')
        ->assertJsonPath('data.upload.file_size', 2048);

    expect($response->json('data.pdf_path'))->toStartWith('quick-uploads/'.$user->id.'/');
    expect(QuickUpload::query()->count())->toBe(0);
});

it('requires authentication to request a quick upload url', function () {
    $this->postJson('/api/v1/quick-uploads/upload-url', [
        'file_name' => 'midterm.pdf',
        'content_type' => 'application/pdf',
        'file_size' => 2048,
    ])->assertUnauthorized();
});

it('validates that quick upload url requests are pdf files within the allowed size', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/quick-uploads/upload-url', [
        'file_name' => 'midterm.png',
        'content_type' => 'image/png',
        'file_size' => 10485761,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'file_name',
            'content_type',
            'file_size',
        ]);
});

it('creates a quick upload after confirming the uploaded file exists on s3', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $disk = \Mockery::mock(FilesystemAdapter::class);

    Storage::shouldReceive('disk')->once()->with('s3')->andReturn($disk);

    $disk->shouldReceive('exists')
        ->once()
        ->with('quick-uploads/'.$user->id.'/completed-upload.pdf')
        ->andReturn(true);

    $disk->shouldReceive('size')
        ->once()
        ->with('quick-uploads/'.$user->id.'/completed-upload.pdf')
        ->andReturn(4096);

    $response = $this->postJson('/api/v1/quick-uploads', [
        'pdf_path' => 'quick-uploads/'.$user->id.'/completed-upload.pdf',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.status', QuickUploadStatus::Pending->value)
        ->assertJsonPath('data.pdf_path', 'quick-uploads/'.$user->id.'/completed-upload.pdf')
        ->assertJsonPath('data.pdf_size', 4096);

    $this->assertDatabaseHas('quick_uploads', [
        'user_id' => $user->id,
        'pdf_path' => 'quick-uploads/'.$user->id.'/completed-upload.pdf',
        'pdf_size' => 4096,
        'status' => QuickUploadStatus::Pending->value,
    ]);
});

it('rejects quick upload finalization when the file does not exist on s3', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $disk = \Mockery::mock(FilesystemAdapter::class);

    Storage::shouldReceive('disk')->once()->with('s3')->andReturn($disk);

    $disk->shouldReceive('exists')
        ->once()
        ->with('quick-uploads/'.$user->id.'/missing-upload.pdf')
        ->andReturn(false);

    $this->postJson('/api/v1/quick-uploads', [
        'pdf_path' => 'quick-uploads/'.$user->id.'/missing-upload.pdf',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['pdf_path']);
});

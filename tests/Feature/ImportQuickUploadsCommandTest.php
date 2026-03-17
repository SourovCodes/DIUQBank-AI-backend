<?php

use App\Enums\QuickUploadStatus;
use App\Jobs\CompressQuickUploadPdf;
use App\Models\QuickUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('imports quick uploads while reusing users and skipping invalid items', function (): void {
    Storage::fake('s3');
    Queue::fake();

    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'username' => 'existing',
    ]);

    Http::fake([
        'https://diuqbank.com/public/submissions*' => Http::response([
            'data' => [
                [
                    'id' => 101,
                    'user' => [
                        'email' => 'existing@example.com',
                        'name' => 'Existing User',
                        'username' => 'existing',
                    ],
                    'pdf_original_temporary_url' => 'https://files.test/existing.pdf',
                    'media' => [
                        ['file_name' => 'existing.pdf'],
                    ],
                    'created_at' => '2026-03-01 10:00:00',
                    'updated_at' => '2026-03-01 10:05:00',
                ],
                [
                    'id' => 102,
                    'user' => [
                        'email' => 'new@example.com',
                        'name' => 'New User',
                        'username' => 'existing',
                    ],
                    'pdf_original_temporary_url' => 'https://files.test/new-one.pdf',
                    'media' => [
                        ['file_name' => 'new-one.pdf'],
                    ],
                    'created_at' => '2026-03-02 10:00:00',
                    'updated_at' => '2026-03-02 10:05:00',
                ],
                [
                    'id' => 103,
                    'user' => [
                        'email' => 'new@example.com',
                        'name' => 'New User',
                        'username' => 'existing',
                    ],
                    'pdf_original_temporary_url' => 'https://files.test/new-two.pdf',
                    'media' => [
                        ['file_name' => 'new-two.pdf'],
                    ],
                    'created_at' => '2026-03-03 10:00:00',
                    'updated_at' => '2026-03-03 10:05:00',
                ],
                [
                    'id' => 104,
                    'user' => [
                        'name' => 'Missing Email',
                    ],
                    'pdf_original_temporary_url' => 'https://files.test/missing-user.pdf',
                ],
                [
                    'id' => 105,
                    'user' => [
                        'email' => 'missing-pdf@example.com',
                        'name' => 'Missing Pdf',
                    ],
                ],
            ],
            'links' => [
                'next' => null,
            ],
        ], 200),
        'https://files.test/*' => Http::response('%PDF-1.4 fake pdf content', 200, [
            'Content-Type' => 'application/pdf',
        ]),
    ]);

    $this->artisan('app:import-quick-uploads', ['--concurrency' => 2])
        ->expectsOutputToContain('Successfully imported submission ID 101')
        ->expectsOutputToContain('Successfully imported submission ID 102')
        ->expectsOutputToContain('Successfully imported submission ID 103')
        ->assertExitCode(0);

    expect(User::query()->count())->toBe(2)
        ->and(QuickUpload::query()->count())->toBe(3)
        ->and(User::query()->where('email', 'new@example.com')->value('username'))->toBe('existing_1')
        ->and(QuickUpload::query()->where('user_id', $existingUser->id)->count())->toBe(1)
        ->and(QuickUpload::query()->where('status', QuickUploadStatus::Pending->value)->count())->toBe(3)
        ->and(Storage::disk('s3')->allFiles('quick-uploads'))->toHaveCount(3);

    Queue::assertPushed(CompressQuickUploadPdf::class, 3);
});

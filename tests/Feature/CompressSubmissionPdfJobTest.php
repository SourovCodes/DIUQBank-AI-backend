<?php

use App\Jobs\CompressSubmissionPdf;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('it stores compressed pdf path when compression succeeds', function () {
    Storage::fake('s3');

    $submission = Submission::factory()->create([
        'pdf_path' => 'submissions/original.pdf',
        'compressed_pdf_path' => null,
    ]);

    Storage::disk('s3')->put($submission->pdf_path, '%PDF-1.4 test document');

    $job = new class($submission) extends CompressSubmissionPdf
    {
        protected function runGhostscript(string $sourcePath, string $compressedPath): void
        {
            copy($sourcePath, $compressedPath);
        }
    };

    $job->handle();

    $submission->refresh();

    expect($submission->compressed_pdf_path)->not->toBeNull();

    Storage::disk('s3')->assertExists($submission->compressed_pdf_path);
});

test('it skips compression when source file does not exist', function () {
    Storage::fake('s3');

    $submission = Submission::factory()->create([
        'pdf_path' => 'submissions/missing.pdf',
        'compressed_pdf_path' => null,
    ]);

    $job = new class($submission) extends CompressSubmissionPdf
    {
        protected function runGhostscript(string $sourcePath, string $compressedPath): void
        {
            throw new \RuntimeException('This should not run when source file is missing.');
        }
    };

    $job->handle();

    $submission->refresh();

    expect($submission->compressed_pdf_path)->toBeNull();
});

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
        'pdf_size' => null,
        'compressed_pdf_path' => null,
        'compressed_pdf_size' => null,
    ]);

    $sourceContents = '%PDF-1.4 test document';

    Storage::disk('s3')->put($submission->pdf_path, $sourceContents);

    $job = new class($submission) extends CompressSubmissionPdf
    {
        protected function compressPdf(Submission $submission): array
        {
            $compressedPath = 'submissions/processed/'.$submission->getKey().'/compressed.pdf';

            Storage::disk('s3')->put($compressedPath, 'compressed pdf');

            return [
                'compressed_path' => $compressedPath,
                'compressed_size' => Storage::disk('s3')->size($compressedPath),
                'original_size' => Storage::disk('s3')->size($submission->pdf_path),
            ];
        }
    };

    $job->handle();

    $submission->refresh();

    expect($submission->compressed_pdf_path)->not->toBeNull();
    expect($submission->compressed_pdf_size)->toBe(Storage::disk('s3')->size($submission->compressed_pdf_path));
    expect($submission->pdf_size)->toBe(strlen($sourceContents));

    Storage::disk('s3')->assertExists($submission->compressed_pdf_path);
});

test('it skips compression when source file does not exist', function () {
    Storage::fake('s3');

    $submission = Submission::factory()->create([
        'pdf_path' => 'submissions/missing.pdf',
        'compressed_pdf_path' => null,
        'compressed_pdf_size' => null,
    ]);

    $job = new class($submission) extends CompressSubmissionPdf
    {
        protected function compressPdf(Submission $submission): array
        {
            throw new \RuntimeException('This should not run when source file is missing.');
        }
    };

    $job->handle();

    $submission->refresh();

    expect($submission->compressed_pdf_path)->toBeNull();
    expect($submission->compressed_pdf_size)->toBeNull();
});

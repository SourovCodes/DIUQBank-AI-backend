<?php

use App\Jobs\CompressQuickUploadPdf;
use App\Models\QuickUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('it stores compressed quick upload pdf metadata when compression succeeds', function () {
    Storage::fake('s3');

    $quickUpload = QuickUpload::factory()->create([
        'pdf_path' => 'quick-uploads/original.pdf',
        'pdf_size' => null,
        'compressed_pdf_path' => null,
        'compressed_pdf_size' => null,
    ]);

    $sourceContents = '%PDF-1.4 test quick upload';

    Storage::disk('s3')->put($quickUpload->pdf_path, $sourceContents);

    $job = new class($quickUpload) extends CompressQuickUploadPdf
    {
        protected function compressPdf(QuickUpload $quickUpload): array
        {
            $compressedPath = 'quick-uploads/processed/'.$quickUpload->getKey().'/compressed.pdf';

            Storage::disk('s3')->put($compressedPath, 'compressed quick upload pdf');

            return [
                'compressed_path' => $compressedPath,
                'compressed_size' => Storage::disk('s3')->size($compressedPath),
                'original_size' => Storage::disk('s3')->size($quickUpload->pdf_path),
            ];
        }
    };

    $job->handle();

    $quickUpload->refresh();

    expect($quickUpload->compressed_pdf_path)->not->toBeNull();
    expect($quickUpload->compressed_pdf_size)->toBe(Storage::disk('s3')->size($quickUpload->compressed_pdf_path));
    expect($quickUpload->pdf_size)->toBe(strlen($sourceContents));

    Storage::disk('s3')->assertExists($quickUpload->compressed_pdf_path);
});

test('it skips quick upload compression when source file is missing', function () {
    Storage::fake('s3');

    $quickUpload = QuickUpload::factory()->create([
        'pdf_path' => 'quick-uploads/missing.pdf',
        'compressed_pdf_path' => null,
        'compressed_pdf_size' => null,
    ]);

    $job = new class($quickUpload) extends CompressQuickUploadPdf
    {
        protected function compressPdf(QuickUpload $quickUpload): array
        {
            throw new RuntimeException('This should not run when source file is missing.');
        }
    };

    $job->handle();

    $quickUpload->refresh();

    expect($quickUpload->compressed_pdf_path)->toBeNull();
    expect($quickUpload->compressed_pdf_size)->toBeNull();
});

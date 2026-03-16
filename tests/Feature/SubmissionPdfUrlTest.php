<?php

use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it prefers compressed pdf path for generated url', function () {
    $submission = Submission::factory()->create([
        'pdf_path' => 'submissions/original.pdf',
        'compressed_pdf_path' => 'submissions/compressed.pdf',
    ]);

    $pdfUrl = $submission->getPdfUrl();

    expect($pdfUrl)
        ->not->toBeNull()
        ->toContain('compressed.pdf');
});

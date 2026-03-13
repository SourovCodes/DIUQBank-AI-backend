<?php

use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it prefers watermarked pdf path for generated url', function () {
    $submission = Submission::factory()->create([
        'pdf_path' => 'submissions/original.pdf',
        'watermarked_pdf_path' => 'submissions/watermarked.pdf',
    ]);

    $pdfUrl = $submission->getPdfUrl();

    expect($pdfUrl)
        ->not->toBeNull()
        ->toContain('watermarked.pdf');
});

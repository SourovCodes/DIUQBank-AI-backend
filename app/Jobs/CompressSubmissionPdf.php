<?php

namespace App\Jobs;

use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CompressSubmissionPdf implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(public Submission $submission)
    {
        $this->onQueue('pdf-processing');
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('submission-pdf-compress-'.$this->submission->getKey()))
                ->releaseAfter(30)
                ->expireAfter(300),
        ];
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $submission = $this->submission->fresh();

        if (! $submission instanceof Submission || blank($submission->pdf_path)) {
            return;
        }

        $disk = Storage::disk('s3');

        if (! $disk->exists($submission->pdf_path)) {
            Log::warning('Submission PDF compression skipped because source file was not found.', [
                'submission_id' => $submission->getKey(),
                'pdf_path' => $submission->pdf_path,
            ]);

            return;
        }

        $sourceTempPath = tempnam(sys_get_temp_dir(), 'submission-src-');
        $compressedTempPath = tempnam(sys_get_temp_dir(), 'submission-compressed-');

        if ($sourceTempPath === false || $compressedTempPath === false) {
            throw new RuntimeException('Unable to allocate temporary files for PDF compression.');
        }

        try {
            $sourceContents = $disk->get($submission->pdf_path);

            if (file_put_contents($sourceTempPath, $sourceContents) === false) {
                throw new RuntimeException('Unable to write source PDF to a temporary file.');
            }

            $this->runGhostscript($sourceTempPath, $compressedTempPath);

            $compressedContents = file_get_contents($compressedTempPath);

            if ($compressedContents === false) {
                throw new RuntimeException('Unable to read compressed PDF from temporary file.');
            }

            $compressedPath = sprintf(
                'submissions/processed/%d/%s-compressed.pdf',
                $submission->getKey(),
                now()->format('YmdHis')
            );

            $disk->put($compressedPath, $compressedContents);

            $submission->forceFill([
                'compressed_pdf_path' => $compressedPath,
            ])->save();
        } finally {
            @unlink($sourceTempPath);
            @unlink($compressedTempPath);
        }
    }

    protected function runGhostscript(string $sourcePath, string $compressedPath): void
    {
        $result = Process::timeout(180)->run([
            'gs',
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dPDFSETTINGS=/ebook',
            '-dNOPAUSE',
            '-dQUIET',
            '-dBATCH',
            '-sOutputFile='.$compressedPath,
            $sourcePath,
        ]);

        if ($result->failed()) {
            throw new RuntimeException('Ghostscript compression failed: '.$result->errorOutput());
        }
    }
}

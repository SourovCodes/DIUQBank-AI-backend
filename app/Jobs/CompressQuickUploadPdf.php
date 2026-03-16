<?php

namespace App\Jobs;

use App\Models\QuickUpload;
use App\Services\Pdf\PdfCompressionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompressQuickUploadPdf implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(public QuickUpload $quickUpload)
    {
        $this->onQueue('pdf-processing');
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('quick-upload-pdf-compress-'.$this->quickUpload->getKey()))
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
        $quickUpload = $this->quickUpload->fresh();

        if (! $quickUpload instanceof QuickUpload || blank($quickUpload->pdf_path)) {
            return;
        }

        $previousCompressedPath = $quickUpload->compressed_pdf_path;

        if (! Storage::disk('s3')->exists($quickUpload->pdf_path)) {
            Log::warning('Quick upload PDF compression skipped because source file was not found.', [
                'quick_upload_id' => $quickUpload->getKey(),
                'pdf_path' => $quickUpload->pdf_path,
            ]);

            return;
        }

        $result = $this->compressPdf($quickUpload);

        $quickUpload->forceFill([
            'pdf_size' => $result['original_size'] ?? $quickUpload->pdf_size,
            'compressed_pdf_path' => $result['compressed_path'],
            'compressed_pdf_size' => $result['compressed_size'],
        ])->save();

        if (filled($previousCompressedPath) && $previousCompressedPath !== $result['compressed_path']) {
            Storage::disk('s3')->delete($previousCompressedPath);
        }
    }

    /**
     * @return array{compressed_path: string, compressed_size: int, original_size: int|null}
     */
    protected function compressPdf(QuickUpload $quickUpload): array
    {
        return app(PdfCompressionService::class)->compressStoredPdf(
            sourcePath: $quickUpload->pdf_path,
            destinationDirectory: 'quick-uploads/processed/'.$quickUpload->getKey(),
            destinationFileName: Str::uuid().'-compressed.pdf',
        );
    }
}

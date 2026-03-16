<?php

namespace App\Services\Pdf;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PdfCompressionService
{
    /**
     * @return array{compressed_path: string, compressed_size: int, original_size: int|null}
     */
    public function compressStoredPdf(
        string $sourcePath,
        string $destinationDirectory,
        string $destinationFileName,
        string $disk = 's3',
    ): array {
        $filesystem = Storage::disk($disk);

        if (! $filesystem->exists($sourcePath)) {
            throw new RuntimeException('The source PDF could not be found on storage.');
        }

        $sourceTempPath = tempnam(sys_get_temp_dir(), 'pdf-src-');
        $compressedTempPath = tempnam(sys_get_temp_dir(), 'pdf-compressed-');

        if ($sourceTempPath === false || $compressedTempPath === false) {
            throw new RuntimeException('Unable to allocate temporary files for PDF compression.');
        }

        try {
            $sourceContents = $filesystem->get($sourcePath);

            if (file_put_contents($sourceTempPath, $sourceContents) === false) {
                throw new RuntimeException('Unable to write source PDF to a temporary file.');
            }

            $this->runGhostscript($sourceTempPath, $compressedTempPath);

            $compressedContents = file_get_contents($compressedTempPath);

            if ($compressedContents === false) {
                throw new RuntimeException('Unable to read compressed PDF from temporary file.');
            }

            $compressedPath = trim($destinationDirectory, '/').'/'.trim($destinationFileName, '/');

            $filesystem->put($compressedPath, $compressedContents);

            return [
                'compressed_path' => $compressedPath,
                'compressed_size' => $this->storedFileSize($compressedPath, $disk) ?? strlen($compressedContents),
                'original_size' => $this->storedFileSize($sourcePath, $disk),
            ];
        } finally {
            @unlink($sourceTempPath);
            @unlink($compressedTempPath);
        }
    }

    public function storedFileSize(?string $path, string $disk = 's3'): ?int
    {
        if (blank($path)) {
            return null;
        }

        /** @var FilesystemAdapter $filesystem */
        $filesystem = Storage::disk($disk);

        try {
            if (! $filesystem->exists($path)) {
                return null;
            }

            return $filesystem->size($path);
        } catch (Throwable) {
            return null;
        }
    }

    protected function runGhostscript(string $sourcePath, string $compressedPath): void
    {
        $result = Process::timeout(180)->run([
            'gs',
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dNOPAUSE',
            '-dQUIET',
            '-dBATCH',
            '-dAutoRotatePages=/PageByPage',
            '-dDetectDuplicateImages=true',
            '-dCompressFonts=true',
            '-dFIXEDMEDIA',
            '-dPDFFitPage',
            '-sPAPERSIZE=a4',
            '-dPDFSETTINGS=/ebook',
            '-sOutputFile='.$compressedPath,
            $sourcePath,
        ]);

        if ($result->failed()) {
            throw new RuntimeException('Ghostscript compression failed: '.$result->errorOutput());
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\QuickUploadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CompleteQuickUploadRequest;
use App\Http\Requests\Api\V1\StoreQuickUploadRequest;
use App\Jobs\CompressQuickUploadPdf;
use App\Models\QuickUpload;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuickUploadController extends Controller
{
    public function createUploadUrl(StoreQuickUploadRequest $request): JsonResponse
    {
        $pdfPath = $this->generatePdfPath($request->user()->getKey());
        $expiresAt = now()->addMinutes(10);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('s3');

        $upload = $disk->temporaryUploadUrl($pdfPath, $expiresAt, [
            'ContentType' => $request->contentType(),
        ]);

        return response()->json([
            'data' => [
                'pdf_path' => $pdfPath,
                'upload' => [
                    'method' => 'PUT',
                    'url' => $upload['url'],
                    'headers' => $upload['headers'],
                    'expires_at' => $expiresAt->toISOString(),
                    'content_type' => $request->contentType(),
                    'file_name' => $request->fileName(),
                    'file_size' => $request->fileSize(),
                ],
            ],
        ]);
    }

    public function store(CompleteQuickUploadRequest $request): JsonResponse
    {
        $pdfPath = $request->pdfPath();

        if (! $this->isOwnedPdfPath($pdfPath, $request->user()->getKey())) {
            throw ValidationException::withMessages([
                'pdf_path' => 'The selected upload path is invalid.',
            ]);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('s3');

        if (! $disk->exists($pdfPath)) {
            throw ValidationException::withMessages([
                'pdf_path' => 'The uploaded file could not be found on storage.',
            ]);
        }

        $pdfSize = $disk->size($pdfPath);

        $quickUpload = QuickUpload::query()->create([
            'user_id' => $request->user()->getKey(),
            'pdf_path' => $pdfPath,
            'pdf_size' => $pdfSize,
            'status' => QuickUploadStatus::Pending,
        ]);

        CompressQuickUploadPdf::dispatch($quickUpload)->afterCommit();

        return response()->json([
            'data' => [
                'id' => $quickUpload->getKey(),
                'status' => $quickUpload->status->value,
                'pdf_path' => $quickUpload->pdf_path,
                'pdf_size' => $quickUpload->pdf_size,
                'created_at' => $quickUpload->created_at?->toISOString(),
            ],
        ], 201);
    }

    private function generatePdfPath(int|string $userId): string
    {
        return 'quick-uploads/'.$userId.'/'.Str::uuid().'.pdf';
    }

    private function isOwnedPdfPath(string $pdfPath, int|string $userId): bool
    {
        return Str::startsWith($pdfPath, 'quick-uploads/'.$userId.'/')
            && Str::endsWith(Str::lower($pdfPath), '.pdf');
    }
}

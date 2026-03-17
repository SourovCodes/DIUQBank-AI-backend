<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\QuickUploadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexQuickUploadRequest;
use App\Http\Requests\Api\V1\StoreQuickUploadRequest;
use App\Http\Resources\Api\V1\QuickUploadResource;
use App\Jobs\CompressQuickUploadPdf;
use App\Models\QuickUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class QuickUploadController extends Controller
{
    public function index(IndexQuickUploadRequest $request): AnonymousResourceCollection
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $quickUploads = QuickUpload::query()
            ->whereBelongsTo($user, 'uploader')
            ->latest()
            ->paginate($request->perPage())
            ->withQueryString();

        return QuickUploadResource::collection($quickUploads);
    }

    public function store(StoreQuickUploadRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $pdf = $request->pdf();
        $pdfPath = $this->storePdf($pdf, $user->getKey());

        $quickUpload = QuickUpload::query()->create([
            'user_id' => $user->getKey(),
            'pdf_path' => $pdfPath,
            'pdf_size' => $pdf->getSize(),
            'status' => QuickUploadStatus::Pending,
        ]);

        CompressQuickUploadPdf::dispatch($quickUpload)->afterCommit();

        return QuickUploadResource::make($quickUpload)
            ->response()
            ->setStatusCode(201);
    }

    private function generatePdfPath(int|string $userId): string
    {
        return 'quick-uploads/'.$userId.'/'.Str::uuid().'.pdf';
    }

    private function storePdf(UploadedFile $pdf, int|string $userId): string
    {
        $pdfPath = $this->generatePdfPath($userId);

        $storedPdfPath = $pdf->storeAs(
            dirname($pdfPath),
            basename($pdfPath),
            's3',
        );

        if (! is_string($storedPdfPath)) {
            throw new RuntimeException('Unable to store the uploaded PDF.');
        }

        return $storedPdfPath;
    }
}

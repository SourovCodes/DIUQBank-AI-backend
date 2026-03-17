<?php

namespace App\Models;

use App\Enums\QuickUploadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Throwable;

class QuickUpload extends Model
{
    /** @use HasFactory<\Database\Factories\QuickUploadFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'pdf_path',
        'pdf_size',
        'compressed_pdf_path',
        'compressed_pdf_size',
        'status',
        'reason',
        'ai_processed_at',
        'manual_review_requested_at',
        'reviewer_id',
        'manual_reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pdf_size' => 'integer',
            'compressed_pdf_size' => 'integer',
            'status' => QuickUploadStatus::class,
            'ai_processed_at' => 'datetime',
            'manual_review_requested_at' => 'datetime',
            'manual_reviewed_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function getOriginalPdfUrl(): ?string
    {
        return $this->getStorageUrl($this->pdf_path);
    }

    public function getCompressedPdfUrl(): ?string
    {
        return $this->getStorageUrl($this->compressed_pdf_path);
    }

    public function getPdfUrl(): ?string
    {
        return $this->getCompressedPdfUrl() ?? $this->getOriginalPdfUrl();
    }

    public function getPdfSizeLabel(): ?string
    {
        return filled($this->pdf_size)
            ? Number::fileSize($this->pdf_size)
            : null;
    }

    public function getCompressedPdfSizeLabel(): ?string
    {
        return filled($this->compressed_pdf_size)
            ? Number::fileSize($this->compressed_pdf_size)
            : null;
    }

    public function getPdfSizeDifference(): ?int
    {
        if (blank($this->pdf_size) || blank($this->compressed_pdf_size)) {
            return null;
        }

        return max($this->pdf_size - $this->compressed_pdf_size, 0);
    }

    public function getPdfSizeDifferenceLabel(): ?string
    {
        $difference = $this->getPdfSizeDifference();

        return filled($difference)
            ? Number::fileSize($difference)
            : null;
    }

    protected function getStorageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('s3');

        try {
            return $disk->temporaryUrl($path, now()->addMinutes(10));
        } catch (Throwable) {
            return $disk->url($path);
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

class Submission extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'question_id',
        'section',
        'batch',
        'pdf_path',
        'watermarked_pdf_path',
        'views',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'views' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function getPdfUrl(): ?string
    {
        $pdfPath = filled($this->watermarked_pdf_path)
            ? $this->watermarked_pdf_path
            : $this->pdf_path;

        if (blank($pdfPath)) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('s3');

        try {
            return $disk->temporaryUrl($pdfPath, now()->addMinutes(10));
        } catch (Throwable) {
            return $disk->url($pdfPath);
        }
    }
}

<?php

namespace App\Models;

use App\Enums\QuickUploadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'status',
        'ai_rejection_reason',
        'ai_processed_at',
        'manual_review_requested_at',
        'reviewer_id',
        'manual_rejection_reason',
        'manual_reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
}

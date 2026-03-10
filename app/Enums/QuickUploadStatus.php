<?php

namespace App\Enums;

enum QuickUploadStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case AiApproved = 'ai_approved';
    case AiRejected = 'ai_rejected';
    case ManualReviewRequested = 'manual_review_requested';
    case ManualApproved = 'manual_approved';
    case ManualRejected = 'manual_rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::AiApproved => 'AI Approved',
            self::AiRejected => 'AI Rejected',
            self::ManualReviewRequested => 'Manual Review Requested',
            self::ManualApproved => 'Manual Approved',
            self::ManualRejected => 'Manual Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Processing => 'warning',
            self::AiApproved, self::ManualApproved => 'success',
            self::AiRejected, self::ManualRejected => 'danger',
            self::ManualReviewRequested => 'primary',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

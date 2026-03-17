<?php

namespace App\Filament\Widgets;

use App\Enums\QuickUploadStatus;
use App\Models\Question;
use App\Models\QuickUpload;
use App\Models\Submission;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $reviewQueueCount = QuickUpload::query()
            ->whereIn('status', [
                QuickUploadStatus::Pending,
                QuickUploadStatus::Processing,
                QuickUploadStatus::AiRejected,
                QuickUploadStatus::ManualReviewRequested,
            ])
            ->count();

        $recentSubmissionCount = Submission::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $recentQuickUploadCount = QuickUpload::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return [
            Stat::make('Question Bank', number_format(Question::query()->count()))
                ->description(number_format(Submission::query()->count()).' submissions indexed')
                ->color('primary'),
            Stat::make('Contributors', number_format(User::contributors()->count()))
                ->description(number_format(User::query()->count()).' total users')
                ->color('success'),
            Stat::make('Review Queue', number_format($reviewQueueCount))
                ->description('Quick uploads awaiting admin action')
                ->color($reviewQueueCount > 0 ? 'warning' : 'success'),
            Stat::make('Uploads This Week', number_format($recentSubmissionCount + $recentQuickUploadCount))
                ->description(number_format($recentSubmissionCount).' submissions and '.number_format($recentQuickUploadCount).' quick uploads')
                ->color('gray'),
        ];
    }
}

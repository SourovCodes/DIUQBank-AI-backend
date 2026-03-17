<?php

namespace App\Filament\Widgets;

use App\Models\QuickUpload;
use App\Models\Submission;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class UploadActivityChart extends ChartWidget
{
    protected ?string $heading = 'Seven-Day Upload Activity';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $labels = collect(range(6, 0))
            ->map(fn (int $daysAgo) => now()->startOfDay()->subDays($daysAgo));

        $submissionCounts = Submission::query()
            ->selectRaw('DATE(created_at) as created_date, COUNT(*) as aggregate')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('created_date')
            ->pluck('aggregate', 'created_date');

        $quickUploadCounts = QuickUpload::query()
            ->selectRaw('DATE(created_at) as created_date, COUNT(*) as aggregate')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('created_date')
            ->pluck('aggregate', 'created_date');

        return [
            'datasets' => [
                [
                    'label' => 'Submissions',
                    'data' => $labels
                        ->map(fn (Carbon $date): int => (int) ($submissionCounts[$date->toDateString()] ?? 0))
                        ->all(),
                    'borderColor' => '#d97706',
                    'backgroundColor' => 'rgba(217, 119, 6, 0.12)',
                ],
                [
                    'label' => 'Quick uploads',
                    'data' => $labels
                        ->map(fn (Carbon $date): int => (int) ($quickUploadCounts[$date->toDateString()] ?? 0))
                        ->all(),
                    'borderColor' => '#475569',
                    'backgroundColor' => 'rgba(71, 85, 105, 0.12)',
                ],
            ],
            'labels' => $labels
                ->map(fn (Carbon $date): string => $date->format('M j'))
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

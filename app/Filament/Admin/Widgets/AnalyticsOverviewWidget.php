<?php

namespace App\Filament\Admin\Widgets;

class AnalyticsOverviewWidget extends AnalyticsDashboardWidget
{
    protected static ?int $sort = 60;

    protected function heading(): string
    {
        return 'Web Traffic Overview';
    }

    protected function description(): ?string
    {
        return 'Basic first-party page view and visitor counts.';
    }

    protected function emptyMessage(): string
    {
        return 'No public page views have been tracked yet.';
    }

    protected function rows(): array
    {
        $today = now()->startOfDay();
        $sevenDaysAgo = now()->subDays(7);
        $thirtyDaysAgo = now()->subDays(30);

        if ($this->analyticsQuery()->count() === 0) {
            return [];
        }

        return [
            $this->row(
                type: '',
                title: 'Views today',
                meta: 'Public HTML page views since midnight.',
                status: number_format($this->analyticsQuery()->where('viewed_at', '>=', $today)->count()),
                statusColor: 'info',
            ),
            $this->row(
                type: '',
                title: 'Views last 7 days',
                meta: 'All tracked public page views in the last week.',
                status: number_format($this->analyticsQuery()->where('viewed_at', '>=', $sevenDaysAgo)->count()),
                statusColor: 'info',
            ),
            $this->row(
                type: '',
                title: 'Unique visitors last 7 days',
                meta: 'Counted by distinct session hash.',
                status: number_format($this->analyticsQuery()->where('viewed_at', '>=', $sevenDaysAgo)->distinct('session_hash')->count('session_hash')),
                statusColor: 'success',
            ),
            $this->row(
                type: '',
                title: 'Unique visitors last 30 days',
                meta: 'Counted by distinct session hash.',
                status: number_format($this->analyticsQuery()->where('viewed_at', '>=', $thirtyDaysAgo)->distinct('session_hash')->count('session_hash')),
                statusColor: 'success',
            ),
        ];
    }
}

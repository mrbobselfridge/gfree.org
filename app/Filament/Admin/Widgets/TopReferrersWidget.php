<?php

namespace App\Filament\Admin\Widgets;

use App\Models\AnalyticsPageView;

class TopReferrersWidget extends AnalyticsDashboardWidget
{
    protected static ?int $sort = 80;

    protected function heading(): string
    {
        return 'Referrer Traffic';
    }

    protected function description(): ?string
    {
        return 'Where public page views came from in the last 30 days.';
    }

    protected function emptyMessage(): string
    {
        return 'No referrer data is available yet.';
    }

    protected function rows(): array
    {
        $since = now()->subDays(30);
        $directViews = $this->analyticsQuery()
            ->where('viewed_at', '>=', $since)
            ->whereNull('referrer_domain')
            ->count();

        $rows = $this->analyticsQuery()
            ->selectRaw('referrer_domain, COUNT(*) as views')
            ->where('viewed_at', '>=', $since)
            ->whereNotNull('referrer_domain')
            ->groupBy('referrer_domain')
            ->orderByDesc('views')
            ->limit(7)
            ->get()
            ->map(fn (AnalyticsPageView $referrer): array => $this->row(
                type: '',
                title: $referrer->referrer_domain,
                meta: 'External referring domain',
                status: number_format((int) $referrer->views),
                statusColor: 'info',
            ));

        if ($directViews > 0) {
            $rows->prepend($this->row(
                type: '',
                title: 'Direct / unknown',
                meta: 'No external referrer was sent.',
                status: number_format($directViews),
                statusColor: 'gray',
            ));
        }

        return $rows->take(8)->values()->all();
    }
}

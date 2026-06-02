<?php

namespace App\Filament\Admin\Widgets;

class TopPagesWidget extends AnalyticsDashboardWidget
{
    protected static ?int $sort = 70;

    protected function heading(): string
    {
        return 'Top Pages';
    }

    protected function description(): ?string
    {
        return 'Top 10 most-viewed public pages in the last 30 days.';
    }

    protected function emptyMessage(): string
    {
        return 'No page view data is available yet.';
    }

    protected function rows(): array
    {
        return $this->analyticsQuery()
            ->selectRaw('path, page_title, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors')
            ->where('viewed_at', '>=', now()->subDays(30))
            ->groupBy('path', 'page_title')
            ->orderByDesc('views')
            ->limit(10)
            ->get()
            ->map(fn ($page): array => $this->row(
                type: '',
                title: $page->page_title ?: $page->path,
                meta: "{$page->path} | ".number_format((int) $page->visitors).' visitors',
                status: number_format((int) $page->views),
                statusColor: 'info',
            ))
            ->all();
    }
}

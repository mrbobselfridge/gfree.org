<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Pages\Concerns\RequiresAdminPageAccess;
use App\Models\AnalyticsPageView;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Analytics extends Page
{
    use RequiresAdminPageAccess;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|\UnitEnum|null $navigationGroup = 'Sitewide';

    protected static ?int $navigationSort = 15;

    protected static ?string $navigationLabel = 'Analytics';

    protected static ?string $title = 'Analytics';

    protected static ?string $slug = 'analytics';

    protected string $view = 'filament.admin.pages.analytics';

    public string $range = '30';

    public string $path = '';

    public string $source = 'all';

    public string $device = 'all';

    public function rangeOptions(): array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 90 days',
            '365' => 'Last year',
            'all' => 'All time',
        ];
    }

    public function sourceOptions(): array
    {
        return [
            'all' => 'All traffic',
            'direct' => 'Direct only',
            'referred' => 'Referred only',
        ];
    }

    public function deviceOptions(): array
    {
        return ['all' => 'All devices'] + AnalyticsPageView::query()
            ->whereNotNull('device_type')
            ->select('device_type')
            ->distinct()
            ->orderBy('device_type')
            ->pluck('device_type', 'device_type')
            ->all();
    }

    public function pathOptions(): array
    {
        return ['' => 'All pages'] + AnalyticsPageView::query()
            ->whereNotNull('path')
            ->select('path')
            ->distinct()
            ->orderBy('path')
            ->limit(150)
            ->pluck('path', 'path')
            ->all();
    }

    public function summaryMetrics(): array
    {
        $query = $this->analyticsQuery();
        $views = (clone $query)->count();
        $visitors = (clone $query)->distinct('visitor_hash')->count('visitor_hash');
        $sessions = (clone $query)->distinct('session_hash')->count('session_hash');
        $direct = (clone $query)->whereNull('referrer_domain')->count();
        $referred = (clone $query)->whereNotNull('referrer_domain')->count();

        return [
            [
                'label' => 'Views',
                'value' => number_format($views),
                'meta' => $this->rangeLabel(),
            ],
            [
                'label' => 'Visitors',
                'value' => number_format($visitors),
                'meta' => 'Distinct visitor hashes',
            ],
            [
                'label' => 'Sessions',
                'value' => number_format($sessions),
                'meta' => 'Distinct session hashes',
            ],
            [
                'label' => 'Views / visitor',
                'value' => $visitors > 0 ? number_format($views / $visitors, 1) : '0.0',
                'meta' => 'Engagement signal',
            ],
            [
                'label' => 'Direct',
                'value' => number_format($direct),
                'meta' => $this->percentageLabel($direct, $views),
            ],
            [
                'label' => 'Referred',
                'value' => number_format($referred),
                'meta' => $this->percentageLabel($referred, $views),
            ],
        ];
    }

    public function dailyTrend(): Collection
    {
        $query = $this->analyticsQuery();
        $days = $this->trendDays();
        $start = now()->startOfDay()->subDays($days - 1);

        $rows = (clone $query)
            ->where('viewed_at', '>=', $start)
            ->selectRaw('DATE(viewed_at) as day, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $trend = collect();

        for ($offset = 0; $offset < $days; $offset++) {
            $day = $start->copy()->addDays($offset);
            $key = $day->toDateString();
            $row = $rows->get($key);

            $trend->push([
                'day' => $key,
                'label' => $day->format($days > 31 ? 'M j' : 'j'),
                'views' => (int) ($row?->views ?? 0),
                'visitors' => (int) ($row?->visitors ?? 0),
            ]);
        }

        return $trend;
    }

    public function topPages(): Collection
    {
        return (clone $this->analyticsQuery())
            ->selectRaw('path, MAX(page_title) as page_title, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors, COUNT(DISTINCT session_hash) as sessions')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(12)
            ->get();
    }

    public function topReferrers(): Collection
    {
        return (clone $this->analyticsQuery())
            ->whereNotNull('referrer_domain')
            ->selectRaw('referrer_domain, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors')
            ->groupBy('referrer_domain')
            ->orderByDesc('views')
            ->limit(10)
            ->get();
    }

    public function deviceBreakdown(): Collection
    {
        return $this->breakdown('device_type');
    }

    public function browserBreakdown(): Collection
    {
        return $this->breakdown('browser');
    }

    public function platformBreakdown(): Collection
    {
        return $this->breakdown('platform');
    }

    public function recentViews(): Collection
    {
        return (clone $this->analyticsQuery())
            ->latest('viewed_at')
            ->limit(20)
            ->get();
    }

    public function maxValue(Collection $rows, string $key): int
    {
        return max(1, (int) $rows->max($key));
    }

    public function percentageWidth(int|float $value, int|float $max): string
    {
        return number_format(max(2, min(100, $max > 0 ? ($value / $max) * 100 : 0)), 2, '.', '').'%';
    }

    protected function analyticsQuery(): Builder
    {
        $query = AnalyticsPageView::query();

        if ($this->range !== 'all') {
            $query->where('viewed_at', '>=', now()->subDays((int) $this->range));
        }

        if (filled($this->path)) {
            $query->where('path', $this->path);
        }

        if ($this->source === 'direct') {
            $query->whereNull('referrer_domain');
        }

        if ($this->source === 'referred') {
            $query->whereNotNull('referrer_domain');
        }

        if ($this->device !== 'all') {
            $query->where('device_type', $this->device);
        }

        return $query;
    }

    private function breakdown(string $column): Collection
    {
        return (clone $this->analyticsQuery())
            ->selectRaw("COALESCE({$column}, 'Unknown') as label, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors")
            ->groupBy('label')
            ->orderByDesc('views')
            ->limit(8)
            ->get();
    }

    private function rangeLabel(): string
    {
        return $this->rangeOptions()[$this->range] ?? 'Selected range';
    }

    private function percentageLabel(int $count, int $total): string
    {
        if ($total === 0) {
            return '0% of views';
        }

        return number_format(($count / $total) * 100, 1).'% of views';
    }

    private function trendDays(): int
    {
        if ($this->range === 'all') {
            $firstView = AnalyticsPageView::query()->oldest('viewed_at')->value('viewed_at');

            if (! $firstView) {
                return 30;
            }

            return min(90, max(7, Carbon::parse($firstView)->diffInDays(now()) + 1));
        }

        return min(90, max(7, (int) $this->range));
    }
}

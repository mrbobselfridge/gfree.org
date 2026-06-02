<?php

namespace App\Filament\Admin\Widgets;

class BrowsersDevicesWidget extends AnalyticsDashboardWidget
{
    protected static ?int $sort = 90;

    protected function heading(): string
    {
        return 'Browsers / Devices';
    }

    protected function description(): ?string
    {
        return 'Browser and device mix from public page views in the last 30 days.';
    }

    protected function emptyMessage(): string
    {
        return 'No browser or device data is available yet.';
    }

    protected function rows(): array
    {
        $since = now()->subDays(30);

        $devices = $this->analyticsQuery()
            ->selectRaw('device_type as label, COUNT(*) as views')
            ->where('viewed_at', '>=', $since)
            ->groupBy('device_type')
            ->orderByDesc('views')
            ->limit(4)
            ->get()
            ->map(fn ($device): array => $this->row(
                type: 'Device',
                title: $device->label ?: 'Unknown',
                meta: 'Device type',
                status: number_format((int) $device->views),
                statusColor: 'success',
            ));

        $browsers = $this->analyticsQuery()
            ->selectRaw('browser as label, COUNT(*) as views')
            ->where('viewed_at', '>=', $since)
            ->groupBy('browser')
            ->orderByDesc('views')
            ->limit(4)
            ->get()
            ->map(fn ($browser): array => $this->row(
                type: 'Browser',
                title: $browser->label ?: 'Unknown',
                meta: 'Browser family',
                status: number_format((int) $browser->views),
                statusColor: 'info',
            ));

        return $devices->merge($browsers)->take(8)->values()->all();
    }
}

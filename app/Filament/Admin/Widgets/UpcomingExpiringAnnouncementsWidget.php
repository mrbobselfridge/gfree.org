<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Announcements\AnnouncementResource;
use App\Models\Announcement;
use App\Support\AdminAccess;
use Illuminate\Support\Carbon;

class UpcomingExpiringAnnouncementsWidget extends CmsDashboardWidget
{
    protected static ?int $sort = 30;

    protected function heading(): string
    {
        return 'Upcoming / Expiring Announcements';
    }

    protected function description(): ?string
    {
        return 'Announcement publish, feature, and expiration dates in the next 30 days.';
    }

    protected function emptyMessage(): string
    {
        return 'No announcement schedule dates are coming up soon.';
    }

    protected function actionLabel(): ?string
    {
        return $this->canAccessTool(AdminAccess::ANNOUNCEMENTS) ? 'View all' : null;
    }

    protected function actionUrl(): ?string
    {
        return $this->canAccessTool(AdminAccess::ANNOUNCEMENTS)
            ? $this->resourceUrl(AnnouncementResource::class)
            : null;
    }

    protected function rows(): array
    {
        if (! $this->canAccessTool(AdminAccess::ANNOUNCEMENTS)) {
            return [];
        }

        $now = now();
        $soon = now()->addDays(30);

        return collect([
            ...$this->dateRows('publish_at', 'Publishes', 'info', $now, $soon),
            ...$this->dateRows('featured_at', 'Featured', 'info', $now, $soon),
            ...$this->dateRows('feature_expires_at', 'Feature ends', 'warning', $now, $soon),
            ...$this->dateRows('expires_at', 'Expires', 'danger', $now, $soon),
        ])
            ->sortBy('sortDate')
            ->take(8)
            ->map(fn (array $row): array => $row['display'])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function dateRows(string $field, string $status, string $statusColor, Carbon $now, Carbon $soon): array
    {
        return $this->queryFor(Announcement::class)
            ->whereNotNull($field)
            ->whereBetween($field, [$now, $soon])
            ->orderBy($field)
            ->limit(6)
            ->get()
            ->map(fn (Announcement $announcement): array => [
                'sortDate' => $announcement->getAttribute($field),
                'display' => $this->row(
                    type: 'Announcement',
                    title: $announcement->title,
                    meta: $status.' '.$this->formatDate($announcement->getAttribute($field)),
                    url: $this->editUrl(AnnouncementResource::class, $announcement),
                    status: $status,
                    statusColor: $statusColor,
                ),
            ])
            ->all();
    }
}

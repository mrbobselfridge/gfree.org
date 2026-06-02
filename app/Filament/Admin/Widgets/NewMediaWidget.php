<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\MediaLibrary as MediaLibraryPage;
use App\Support\AdminAccess;
use App\Support\MediaLibrary;
use Illuminate\Support\Carbon;

class NewMediaWidget extends CmsDashboardWidget
{
    protected static ?int $sort = 40;

    protected function heading(): string
    {
        return 'New Media';
    }

    protected function description(): ?string
    {
        return 'Newest uploaded images available in the media library.';
    }

    protected function emptyMessage(): string
    {
        return 'No uploaded images found.';
    }

    protected function actionLabel(): ?string
    {
        return $this->canAccessTool(AdminAccess::MEDIA_LIBRARY) ? 'Open library' : null;
    }

    protected function actionUrl(): ?string
    {
        return $this->canAccessTool(AdminAccess::MEDIA_LIBRARY) ? MediaLibraryPage::getUrl() : null;
    }

    protected function rows(): array
    {
        if (! $this->canAccessTool(AdminAccess::MEDIA_LIBRARY)) {
            return [];
        }

        return MediaLibrary::images()
            ->take(6)
            ->map(fn (array $image): array => $this->row(
                type: 'Media Library',
                title: 'Uploaded image',
                meta: collect([
                    $image['size_for_humans'] ?? null,
                    $image['dimensions_for_humans'] ?? null,
                    'Uploaded '.Carbon::createFromTimestamp((int) $image['modified'])->diffForHumans(),
                    $image['usage_summary'] ?? null,
                ])->filter()->implode(' | '),
                url: MediaLibraryPage::getUrl(),
                status: ($image['usage_count'] ?? 0) > 0 ? 'Used' : 'Unused',
                statusColor: ($image['usage_count'] ?? 0) > 0 ? 'success' : 'gray',
                imageUrl: $image['url'] ?? null,
            ))
            ->values()
            ->all();
    }
}

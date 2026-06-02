<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Announcements\AnnouncementResource;
use App\Filament\Admin\Resources\Bulletins\BulletinResource;
use App\Filament\Admin\Resources\HomepageBanners\HomepageBannerResource;
use App\Filament\Admin\Resources\Ministries\MinistryResource;
use App\Filament\Admin\Resources\NavigationLinks\NavigationLinkResource;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Filament\Admin\Resources\SiteSettings\SiteSettingResource;
use App\Filament\Admin\Resources\StaffMembers\StaffMemberResource;
use App\Models\Announcement;
use App\Models\Bulletin;
use App\Models\HomepageBanner;
use App\Models\Ministry;
use App\Models\NavigationLink;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\StaffMember;
use App\Support\AdminAccess;
use Illuminate\Database\Eloquent\Model;

class RecentUpdatesWidget extends CmsDashboardWidget
{
    protected static ?int $sort = 20;

    protected function heading(): string
    {
        return 'Recent Updates';
    }

    protected function description(): ?string
    {
        return 'The latest CMS records created or changed.';
    }

    protected function emptyMessage(): string
    {
        return 'No recent CMS updates found.';
    }

    protected function rows(): array
    {
        return collect([
            ...$this->recentRows(AdminAccess::ANNOUNCEMENTS, Announcement::class, AnnouncementResource::class, 'An', 'title'),
            ...$this->recentRows(AdminAccess::BULLETINS, Bulletin::class, BulletinResource::class, 'Bt', 'title'),
            ...$this->recentRows(AdminAccess::MINISTRIES, Ministry::class, MinistryResource::class, 'Mn', 'name'),
            ...$this->recentRows(AdminAccess::PAGES, Page::class, PageResource::class, 'Pg', 'title'),
            ...$this->recentRows(AdminAccess::LEADERS, StaffMember::class, StaffMemberResource::class, 'Ld', 'name'),
            ...$this->recentRows(AdminAccess::HOMEPAGE_BANNERS, HomepageBanner::class, HomepageBannerResource::class, 'Hb', 'title'),
            ...$this->recentRows(AdminAccess::NAVIGATION_LINKS, NavigationLink::class, NavigationLinkResource::class, 'Nv', 'label'),
            ...$this->recentRows(AdminAccess::SITE_SETTINGS, SiteSetting::class, SiteSettingResource::class, 'Ss', 'church_name'),
        ])
            ->sortByDesc('sortDate')
            ->take(8)
            ->map(fn (array $row): array => $row['display'])
            ->values()
            ->all();
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<int, array<string, mixed>>
     */
    private function recentRows(string $toolKey, string $modelClass, string $resourceClass, string $type, string $titleField): array
    {
        if (! $this->canAccessTool($toolKey)) {
            return [];
        }

        return $this->queryFor($modelClass)
            ->latest('updated_at')
            ->limit(4)
            ->get()
            ->map(fn (Model $record): array => [
                'sortDate' => $record->updated_at ?? $record->created_at,
                'display' => $this->row(
                    type: $type,
                    title: (string) $record->getAttribute($titleField),
                    meta: 'Updated '.$record->updated_at?->diffForHumans(),
                    url: $this->editUrl($resourceClass, $record),
                    status: $record->getAttribute('is_published') === false ? 'Draft' : 'Open',
                    statusColor: $record->getAttribute('is_published') === false ? 'warning' : 'gray',
                ),
            ])
            ->all();
    }
}

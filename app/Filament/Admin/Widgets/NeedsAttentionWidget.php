<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Announcements\AnnouncementResource;
use App\Filament\Admin\Resources\Bulletins\BulletinResource;
use App\Filament\Admin\Resources\HomepageBanners\HomepageBannerResource;
use App\Filament\Admin\Resources\Ministries\MinistryResource;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Filament\Admin\Resources\StaffMembers\StaffMemberResource;
use App\Models\Announcement;
use App\Models\Bulletin;
use App\Models\HomepageBanner;
use App\Models\Ministry;
use App\Models\Page;
use App\Models\StaffMember;
use App\Support\AdminAccess;
use Illuminate\Database\Eloquent\Model;

class NeedsAttentionWidget extends CmsDashboardWidget
{
    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    protected function heading(): string
    {
        return 'Needs Attention';
    }

    protected function description(): ?string
    {
        return 'Drafts, unpublished content, and bulletin PDFs that still need extracted website content.';
    }

    protected function emptyMessage(): string
    {
        return 'No draft or incomplete CMS items found.';
    }

    protected function rows(): array
    {
        return collect([
            ...$this->unpublishedRows(AdminAccess::ANNOUNCEMENTS, Announcement::class, AnnouncementResource::class, 'An', 'title'),
            ...$this->unpublishedRows(AdminAccess::BULLETINS, Bulletin::class, BulletinResource::class, 'Bt', 'title'),
            ...$this->unpublishedRows(AdminAccess::MINISTRIES, Ministry::class, MinistryResource::class, 'Mn', 'name'),
            ...$this->unpublishedRows(AdminAccess::PAGES, Page::class, PageResource::class, 'Pg', 'title'),
            ...$this->unpublishedRows(AdminAccess::LEADERS, StaffMember::class, StaffMemberResource::class, 'Ld', 'name'),
            ...$this->unpublishedRows(AdminAccess::HOMEPAGE_BANNERS, HomepageBanner::class, HomepageBannerResource::class, 'Hb', 'title'),
            ...$this->bulletinsMissingExtraction(),
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
    private function unpublishedRows(string $toolKey, string $modelClass, string $resourceClass, string $type, string $titleField): array
    {
        if (! $this->canAccessTool($toolKey)) {
            return [];
        }

        return $this->queryFor($modelClass)
            ->where('is_published', false)
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (Model $record): array => [
                'sortDate' => $record->updated_at ?? $record->created_at,
                'display' => $this->row(
                    type: $type,
                    title: (string) $record->getAttribute($titleField),
                    meta: 'Created '.$record->created_at?->diffForHumans(),
                    url: $this->editUrl($resourceClass, $record),
                    status: 'Draft',
                    statusColor: 'warning',
                ),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function bulletinsMissingExtraction(): array
    {
        if (! $this->canAccessTool(AdminAccess::BULLETINS)) {
            return [];
        }

        return $this->queryFor(Bulletin::class)
            ->whereNotNull('pdf_path')
            ->where(fn ($query) => $query->whereNull('extracted_html')->orWhere('extracted_html', ''))
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (Bulletin $bulletin): array => [
                'sortDate' => $bulletin->updated_at ?? $bulletin->created_at,
                'display' => $this->row(
                    type: 'Bt',
                    title: $bulletin->title,
                    meta: 'PDF uploaded but no extracted HTML has been saved.',
                    url: $this->editUrl(BulletinResource::class, $bulletin),
                    status: 'Extract',
                    statusColor: 'danger',
                ),
            ])
            ->all();
    }
}

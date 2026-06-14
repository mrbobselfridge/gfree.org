<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\HomepageContent as HomepageContentPage;
use App\Filament\Admin\Resources\Announcements\AnnouncementResource;
use App\Filament\Admin\Resources\Bulletins\BulletinResource;
use App\Filament\Admin\Resources\HomepageBanners\HomepageBannerResource;
use App\Filament\Admin\Resources\Ministries\MinistryResource;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Filament\Admin\Resources\StaffMembers\StaffMemberResource;
use App\Models\Announcement;
use App\Models\Bulletin;
use App\Models\HomepageBanner;
use App\Models\HomepageContent;
use App\Models\Ministry;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\StaffMember;
use App\Support\AdminAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NeedsAttentionWidget extends CmsDashboardWidget
{
    protected static ?int $sort = 10;

    protected function heading(): string
    {
        return 'Needs Attention';
    }

    protected function description(): ?string
    {
        return 'Drafts, unpublished content, and missing key page setup fields.';
    }

    protected function emptyMessage(): string
    {
        return 'No draft, incomplete, or missing setup items found.';
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{value: int, tone: string, label: string}>
     */
    protected function countBadges(array $rows): array
    {
        $counts = [
            'danger' => 0,
            'warning' => 0,
            'success' => 0,
        ];

        foreach ($rows as $row) {
            $counts[$this->attentionTone($row)]++;
        }

        return collect([
            [
                'value' => $counts['danger'],
                'tone' => 'danger',
                'label' => $counts['danger'].' high priority '.str('item')->plural($counts['danger']),
            ],
            [
                'value' => $counts['warning'],
                'tone' => 'warning',
                'label' => $counts['warning'].' medium priority '.str('item')->plural($counts['warning']),
            ],
            [
                'value' => $counts['success'],
                'tone' => 'success',
                'label' => $counts['success'].' low priority '.str('item')->plural($counts['success']),
            ],
        ])
            ->filter(fn (array $badge): bool => $badge['value'] > 0)
            ->values()
            ->all();
    }

    protected function rows(): array
    {
        return collect([
            ...$this->missingContentSetupRows(),
            ...$this->unpublishedRows(AdminAccess::ANNOUNCEMENTS, Announcement::class, AnnouncementResource::class, 'Announcement', 'title'),
            ...$this->unpublishedRows(AdminAccess::BULLETINS, Bulletin::class, BulletinResource::class, 'Bulletin', 'title'),
            ...$this->unpublishedRows(AdminAccess::MINISTRIES, Ministry::class, MinistryResource::class, 'Ministry', 'name'),
            ...$this->unpublishedRows(AdminAccess::PAGES, Page::class, PageResource::class, 'Page', 'title'),
            ...$this->unpublishedRows(AdminAccess::LEADERS, StaffMember::class, StaffMemberResource::class, 'Leader', 'name'),
            ...$this->unpublishedRows(AdminAccess::HOMEPAGE_BANNERS, HomepageBanner::class, HomepageBannerResource::class, 'Homepage Banner', 'title'),
            ...$this->bulletinsMissingExtraction(),
        ])
            ->sortByDesc('sortDate')
            ->take(8)
            ->map(fn (array $row): array => $row['display'])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function attentionTone(array $row): string
    {
        $statusColor = str((string) ($row['statusColor'] ?? ''))->lower()->toString();
        $status = str((string) ($row['status'] ?? ''))->lower()->toString();

        if (in_array($statusColor, ['danger', 'error'], true) || str($status)->contains(['missing', 'error', 'extract', 'high'])) {
            return 'danger';
        }

        if (in_array($statusColor, ['warning'], true) || str($status)->contains(['draft', 'review', 'warning', 'warn', 'medium'])) {
            return 'warning';
        }

        return 'success';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function missingContentSetupRows(): array
    {
        return collect([
            $this->missingSetupRow('Missing Header Images', $this->missingHeaderImageItems()),
            $this->missingSetupRow('Missing Small Labels', $this->missingSmallLabelItems()),
            $this->missingSetupRow('Missing Intros', $this->missingIntroItems()),
        ])
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array{label: string, url: ?string}>  $items
     */
    private function missingSetupRow(string $title, Collection $items): ?array
    {
        if ($items->isEmpty()) {
            return null;
        }

        return [
            'sortDate' => now()->addYears(10),
            'display' => $this->row(
                type: 'Content Setup',
                title: $title,
                meta: $this->summarizeMissingItems($items),
                url: $items->first()['url'] ?? null,
                status: 'Missing',
                statusColor: 'danger',
            ),
        ];
    }

    /**
     * @return Collection<int, array{label: string, url: ?string}>
     */
    private function missingHeaderImageItems(): Collection
    {
        $items = collect();
        $settings = SiteSetting::query()->first();

        if ($this->canAccessTool(AdminAccess::HOMEPAGE_BANNERS) && ! $this->homepageHasHeaderImage()) {
            $items->push($this->missingItem('Homepage', HomepageBannerResource::getUrl()));
        }

        if ($this->canAccessTool(AdminAccess::HOMEPAGE_BANNERS)) {
            $this->publishedHomepageBanners()
                ->filter(fn (HomepageBanner $banner): bool => blank($banner->image_path))
                ->each(fn (HomepageBanner $banner) => $items->push($this->missingItem('Banner: '.$banner->title, $this->editUrl(HomepageBannerResource::class, $banner))));
        }

        $this->landingImageChecks($settings)->each(function (array $check) use ($items): void {
            if ($this->canAccessTool($check['tool']) && blank(data_get($check['settings'], $check['field']))) {
                $items->push($this->missingItem($check['label'], $check['url']));
            }
        });

        if ($this->canAccessTool(AdminAccess::BULLETINS) && blank($settings?->bulletins_image_path) && $this->queryFor(Bulletin::class)->where('is_published', true)->exists()) {
            $items->push($this->missingItem('Bulletin Individual Pages', BulletinResource::getUrl()));
        }

        if ($this->canAccessTool(AdminAccess::ANNOUNCEMENTS)) {
            $this->queryFor(Announcement::class)
                ->where('is_published', true)
                ->where(fn ($query) => $query->whereNull('image_path')->orWhere('image_path', ''))
                ->latest()
                ->limit(6)
                ->get()
                ->each(fn (Announcement $announcement) => $items->push($this->missingItem('Announcement: '.$announcement->title, $this->editUrl(AnnouncementResource::class, $announcement))));
        }

        if ($this->canAccessTool(AdminAccess::MINISTRIES)) {
            $this->queryFor(Ministry::class)
                ->where('is_published', true)
                ->where(fn ($query) => $query->whereNull('hero_image_path')->orWhere('hero_image_path', ''))
                ->latest()
                ->limit(6)
                ->get()
                ->each(fn (Ministry $ministry) => $items->push($this->missingItem('Ministry: '.$ministry->name, $this->editUrl(MinistryResource::class, $ministry))));
        }

        if ($this->canAccessTool(AdminAccess::PAGES)) {
            $this->queryFor(Page::class)
                ->where('is_published', true)
                ->where('show_page_header', true)
                ->where(fn ($query) => $query->whereNull('hero_image_path')->orWhere('hero_image_path', ''))
                ->latest()
                ->limit(6)
                ->get()
                ->each(fn (Page $page) => $items->push($this->missingItem('Page: '.$page->title, $this->editUrl(PageResource::class, $page))));
        }

        return $items;
    }

    /**
     * @return Collection<int, array{label: string, url: ?string}>
     */
    private function missingSmallLabelItems(): Collection
    {
        $items = collect();
        $settings = SiteSetting::query()->first();
        $homepageContent = HomepageContent::query()->first();

        if ($this->canAccessTool(AdminAccess::HOMEPAGE_CONTENT) && blank($homepageContent?->intro_eyebrow)) {
            $items->push($this->missingItem('Homepage', HomepageContentPage::getUrl()));
        }

        if ($this->canAccessTool(AdminAccess::HOMEPAGE_BANNERS)) {
            $this->publishedHomepageBanners()
                ->filter(fn (HomepageBanner $banner): bool => blank($banner->eyebrow))
                ->each(fn (HomepageBanner $banner) => $items->push($this->missingItem('Banner: '.$banner->title, $this->editUrl(HomepageBannerResource::class, $banner))));
        }

        $this->landingLabelChecks($settings)->each(function (array $check) use ($items): void {
            if ($this->canAccessTool($check['tool']) && blank(data_get($check['settings'], $check['field']))) {
                $items->push($this->missingItem($check['label'], $check['url']));
            }
        });

        if ($this->canAccessTool(AdminAccess::MINISTRIES)) {
            $this->queryFor(Ministry::class)
                ->where('is_published', true)
                ->where(fn ($query) => $query->whereNull('category')->orWhere('category', ''))
                ->latest()
                ->limit(6)
                ->get()
                ->each(fn (Ministry $ministry) => $items->push($this->missingItem('Ministry: '.$ministry->name, $this->editUrl(MinistryResource::class, $ministry))));
        }

        if ($this->canAccessTool(AdminAccess::PAGES)) {
            $this->queryFor(Page::class)
                ->where('is_published', true)
                ->where('show_page_header', true)
                ->where(fn ($query) => $query->whereNull('hero_label')->orWhere('hero_label', ''))
                ->latest()
                ->limit(6)
                ->get()
                ->each(fn (Page $page) => $items->push($this->missingItem('Page: '.$page->title, $this->editUrl(PageResource::class, $page))));
        }

        return $items;
    }

    /**
     * @return Collection<int, array{label: string, url: ?string}>
     */
    private function missingIntroItems(): Collection
    {
        $items = collect();
        $settings = SiteSetting::query()->first();
        $homepageContent = HomepageContent::query()->first();

        if ($this->canAccessTool(AdminAccess::HOMEPAGE_CONTENT) && $this->blankContent($homepageContent?->intro_body)) {
            $items->push($this->missingItem('Homepage', HomepageContentPage::getUrl()));
        }

        if ($this->canAccessTool(AdminAccess::HOMEPAGE_BANNERS)) {
            $this->publishedHomepageBanners()
                ->filter(fn (HomepageBanner $banner): bool => $this->blankContent($banner->subtitle))
                ->each(fn (HomepageBanner $banner) => $items->push($this->missingItem('Banner: '.$banner->title, $this->editUrl(HomepageBannerResource::class, $banner))));
        }

        $this->landingIntroChecks($settings)->each(function (array $check) use ($items): void {
            if ($this->canAccessTool($check['tool']) && $this->blankContent(data_get($check['settings'], $check['field']))) {
                $items->push($this->missingItem($check['label'], $check['url']));
            }
        });

        if ($this->canAccessTool(AdminAccess::ANNOUNCEMENTS)) {
            $this->queryFor(Announcement::class)
                ->where('is_published', true)
                ->where(fn ($query) => $query->whereNull('summary')->orWhere('summary', ''))
                ->latest()
                ->limit(6)
                ->get()
                ->each(fn (Announcement $announcement) => $items->push($this->missingItem('Announcement: '.$announcement->title, $this->editUrl(AnnouncementResource::class, $announcement))));
        }

        if ($this->canAccessTool(AdminAccess::BULLETINS)) {
            $this->queryFor(Bulletin::class)
                ->where('is_published', true)
                ->where(fn ($query) => $query->whereNull('extracted_html')->orWhere('extracted_html', ''))
                ->latest()
                ->limit(6)
                ->get()
                ->each(fn (Bulletin $bulletin) => $items->push($this->missingItem('Bulletin: '.$bulletin->title, $this->editUrl(BulletinResource::class, $bulletin))));
        }

        if ($this->canAccessTool(AdminAccess::MINISTRIES)) {
            $this->queryFor(Ministry::class)
                ->where('is_published', true)
                ->where(fn ($query) => $query->whereNull('short_summary')->orWhere('short_summary', ''))
                ->latest()
                ->limit(6)
                ->get()
                ->each(fn (Ministry $ministry) => $items->push($this->missingItem('Ministry: '.$ministry->name, $this->editUrl(MinistryResource::class, $ministry))));
        }

        if ($this->canAccessTool(AdminAccess::PAGES)) {
            $this->queryFor(Page::class)
                ->where('is_published', true)
                ->where('show_page_header', true)
                ->where(fn ($query) => $query->whereNull('intro')->orWhere('intro', ''))
                ->latest()
                ->limit(6)
                ->get()
                ->each(fn (Page $page) => $items->push($this->missingItem('Page: '.$page->title, $this->editUrl(PageResource::class, $page))));
        }

        return $items;
    }

    /**
     * @return Collection<int, HomepageBanner>
     */
    private function publishedHomepageBanners(): Collection
    {
        $now = now();

        return HomepageBanner::query()
            ->where('is_published', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->latest()
            ->get();
    }

    private function homepageHasHeaderImage(): bool
    {
        return $this->publishedHomepageBanners()
            ->contains(fn (HomepageBanner $banner): bool => filled($banner->image_path));
    }

    /**
     * @return Collection<int, array{label: string, tool: string, field: string, settings: ?SiteSetting, url: ?string}>
     */
    private function landingImageChecks(?SiteSetting $settings): Collection
    {
        return collect([
            $this->landingCheck('Announcements Landing Page', AdminAccess::ANNOUNCEMENTS, 'announcements_image_path', $settings, AnnouncementResource::getUrl()),
            $this->landingCheck('Bulletins Landing Page', AdminAccess::BULLETINS, 'bulletins_image_path', $settings, BulletinResource::getUrl()),
            $this->landingCheck('Ministry Landing Page', AdminAccess::MINISTRIES, 'ministry_image_path', $settings, MinistryResource::getUrl()),
            $this->landingCheck('Leaders Landing Page', AdminAccess::LEADERS, 'leadership_image_path', $settings, StaffMemberResource::getUrl()),
        ]);
    }

    /**
     * @return Collection<int, array{label: string, tool: string, field: string, settings: ?SiteSetting, url: ?string}>
     */
    private function landingLabelChecks(?SiteSetting $settings): Collection
    {
        return collect([
            $this->landingCheck('Announcements Landing Page', AdminAccess::ANNOUNCEMENTS, 'announcements_small_label', $settings, AnnouncementResource::getUrl()),
            $this->landingCheck('Bulletins Landing Page', AdminAccess::BULLETINS, 'bulletins_small_label', $settings, BulletinResource::getUrl()),
            $this->landingCheck('Ministry Landing Page', AdminAccess::MINISTRIES, 'ministry_small_label', $settings, MinistryResource::getUrl()),
            $this->landingCheck('Leaders Landing Page', AdminAccess::LEADERS, 'leadership_small_label', $settings, StaffMemberResource::getUrl()),
        ]);
    }

    /**
     * @return Collection<int, array{label: string, tool: string, field: string, settings: ?SiteSetting, url: ?string}>
     */
    private function landingIntroChecks(?SiteSetting $settings): Collection
    {
        return collect([
            $this->landingCheck('Announcements Landing Page', AdminAccess::ANNOUNCEMENTS, 'announcements_subtitle', $settings, AnnouncementResource::getUrl()),
            $this->landingCheck('Bulletins Landing Page', AdminAccess::BULLETINS, 'bulletins_subtitle', $settings, BulletinResource::getUrl()),
            $this->landingCheck('Ministry Landing Page', AdminAccess::MINISTRIES, 'ministry_subtitle', $settings, MinistryResource::getUrl()),
            $this->landingCheck('Leaders Landing Page', AdminAccess::LEADERS, 'leadership_subtitle', $settings, StaffMemberResource::getUrl()),
        ]);
    }

    /**
     * @return array{label: string, tool: string, field: string, settings: ?SiteSetting, url: ?string}
     */
    private function landingCheck(string $label, string $tool, string $field, ?SiteSetting $settings, ?string $url): array
    {
        return compact('label', 'tool', 'field', 'settings', 'url');
    }

    /**
     * @return array{label: string, url: ?string}
     */
    private function missingItem(string $label, ?string $url = null): array
    {
        return compact('label', 'url');
    }

    /**
     * @param  Collection<int, array{label: string, url: ?string}>  $items
     */
    private function summarizeMissingItems(Collection $items): string
    {
        $labels = $items->pluck('label');
        $visible = $labels->take(5)->implode(', ');
        $remaining = $labels->count() - 5;

        return $remaining > 0
            ? "{$visible}, and {$remaining} more."
            : "{$visible}.";
    }

    private function blankContent(mixed $value): bool
    {
        if (is_array($value)) {
            return collect($value)->filter(fn (mixed $item): bool => ! $this->blankContent($item))->isEmpty();
        }

        return blank(trim(strip_tags((string) $value)));
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
                    type: 'Bulletin',
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

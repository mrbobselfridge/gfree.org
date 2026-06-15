<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\HomepageContent as HomepageContentPage;
use App\Filament\Admin\Resources\HomepageBanners\HomepageBannerResource;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Models\HomepageBanner;
use App\Models\HomepageContent;
use App\Models\Page;
use App\Models\SiteSetting;
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
            ...$this->unpublishedRows(AdminAccess::PAGES, Page::class, PageResource::class, 'Page', 'title'),
            ...$this->unpublishedRows(AdminAccess::HOMEPAGE_BANNERS, HomepageBanner::class, HomepageBannerResource::class, 'Homepage Banner', 'title'),
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

        if ($this->canAccessTool(AdminAccess::HOMEPAGE_BANNERS) && ! $this->homepageHasHeaderImage()) {
            $items->push($this->missingItem('Homepage', HomepageBannerResource::getUrl()));
        }

        if ($this->canAccessTool(AdminAccess::HOMEPAGE_BANNERS)) {
            $this->publishedHomepageBanners()
                ->filter(fn (HomepageBanner $banner): bool => blank($banner->image_path))
                ->each(fn (HomepageBanner $banner) => $items->push($this->missingItem('Banner: '.$banner->title, $this->editUrl(HomepageBannerResource::class, $banner))));
        }

        $settings = SiteSetting::query()->first();

        if ($this->canAccessTool(AdminAccess::PAGES) && blank($settings?->default_page_header_image_path)) {
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
        $homepageContent = HomepageContent::query()->first();

        if ($this->canAccessTool(AdminAccess::HOMEPAGE_CONTENT) && blank($homepageContent?->intro_eyebrow)) {
            $items->push($this->missingItem('Homepage', HomepageContentPage::getUrl()));
        }

        if ($this->canAccessTool(AdminAccess::HOMEPAGE_BANNERS)) {
            $this->publishedHomepageBanners()
                ->filter(fn (HomepageBanner $banner): bool => blank($banner->eyebrow))
                ->each(fn (HomepageBanner $banner) => $items->push($this->missingItem('Banner: '.$banner->title, $this->editUrl(HomepageBannerResource::class, $banner))));
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
        $homepageContent = HomepageContent::query()->first();

        if ($this->canAccessTool(AdminAccess::HOMEPAGE_CONTENT) && $this->blankContent($homepageContent?->intro_body)) {
            $items->push($this->missingItem('Homepage', HomepageContentPage::getUrl()));
        }

        if ($this->canAccessTool(AdminAccess::HOMEPAGE_BANNERS)) {
            $this->publishedHomepageBanners()
                ->filter(fn (HomepageBanner $banner): bool => $this->blankContent($banner->subtitle))
                ->each(fn (HomepageBanner $banner) => $items->push($this->missingItem('Banner: '.$banner->title, $this->editUrl(HomepageBannerResource::class, $banner))));
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
}

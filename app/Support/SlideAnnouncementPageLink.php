<?php

namespace App\Support;

use App\Filament\Admin\Resources\Pages\PageResource;
use App\Models\Page;
use App\Models\SlideDeckSlide;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class SlideAnnouncementPageLink
{
    public function announcementsParent(): ?Page
    {
        return Page::query()
            ->where('slug', 'announcements')
            ->first();
    }

    public function matchingPage(SlideDeckSlide $slide): ?Page
    {
        $parent = $this->announcementsParent();

        if (! $parent) {
            return null;
        }

        $slideTokens = $this->tokens(collect([
            $slide->event_title,
            $slide->suggested_name,
            $slide->summary,
            $slide->extracted_text,
            $slide->event_date,
            $slide->event_time,
            $slide->event_location,
        ])->filter()->implode(' '));

        if ($slideTokens === []) {
            return null;
        }

        return $parent->childPages()
            ->get(['id', 'title', 'slug', 'intro', 'content_blocks'])
            ->map(fn (Page $page): array => [
                'page' => $page,
                'score' => $this->score($slideTokens, $this->pageTokens($page)),
            ])
            ->filter(fn (array $match): bool => $match['score'] >= 0.45)
            ->sortByDesc('score')
            ->pluck('page')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function createPageDefaults(SlideDeckSlide $slide): array
    {
        $slide->loadMissing('deck');

        $parent = $this->announcementsParent();
        $publicImagePath = app(SlideDeckPublicImage::class)->ensure($slide);
        $title = $this->pageTitle($slide);
        $parentSlug = trim((string) ($parent?->slug ?: 'announcements'), '/');

        return [
            'title' => $title,
            'hero_label' => $this->slideTypeLabel($slide),
            'is_published' => true,
            'intro' => $slide->summary,
            'slug' => $this->uniqueSlug($parentSlug.'/'.Str::slug($title)),
            'hero_image_path' => $parent?->hero_image_path,
            'card_image_path' => $publicImagePath,
            'parent_page_id' => $parent?->getKey(),
            'publish_at' => now(),
            'expires_at' => $this->expiresAt($slide),
            'content_blocks' => [
                [
                    'type' => 'image_text',
                    'data' => [
                        'eyebrow' => $slide->suggested_name,
                        'background' => 'white',
                        'content_width' => 'wide',
                        'image_position' => 'left',
                        'image_path' => $publicImagePath,
                        'image_alt' => $title,
                        'body' => $this->visibleTextBullets($slide),
                    ],
                ],
            ],
        ];
    }

    public function createPageUrl(SlideDeckSlide $slide): string
    {
        return PageResource::getUrl('create', ['slide_deck_slide' => $slide->getKey()]);
    }

    public function editPageUrl(Page $page): string
    {
        return PageResource::getUrl('edit', ['record' => $page]);
    }

    public function statusLabel(SlideDeckSlide $slide): string
    {
        return $this->matchingPage($slide) ? 'Exists?' : 'Missing';
    }

    public function statusHtml(SlideDeckSlide $slide): HtmlString
    {
        $page = $this->matchingPage($slide);
        $status = $page ? 'Exists?' : 'Missing';
        $color = $page ? 'text-warning-700 dark:text-warning-300' : 'text-danger-700 dark:text-danger-300';

        return new HtmlString('<span class="font-medium '.$color.'">'.e($status).'</span>');
    }

    private function pageTitle(SlideDeckSlide $slide): string
    {
        return (string) str($slide->event_title ?: $slide->suggested_name ?: 'Slide '.$slide->slide_number)
            ->squish();
    }

    private function slideTypeLabel(SlideDeckSlide $slide): string
    {
        return SlideDeckSlide::types()[$slide->slide_type] ?? str((string) $slide->slide_type)->headline()->toString();
    }

    private function visibleTextBullets(SlideDeckSlide $slide): string
    {
        $items = collect(preg_split('/\r\n|\r|\n|•|·| - /', (string) $slide->extracted_text) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->unique()
            ->take(12)
            ->values();

        if ($items->isEmpty()) {
            $items = collect([$slide->announcement_details ?: $slide->summary ?: $this->pageTitle($slide)])
                ->filter();
        }

        return '<ul>'.$items
            ->map(fn (string $item): string => '<li>'.e($item).'</li>')
            ->implode('').'</ul>';
    }

    private function expiresAt(SlideDeckSlide $slide): ?Carbon
    {
        $date = $this->lastDateFrom((string) $slide->event_date);

        return $date?->setTime(22, 0);
    }

    private function lastDateFrom(string $value): ?Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $normalized = preg_replace('/\s+(through|thru|to)\s+/i', ' - ', $value) ?: $value;
        $parts = collect(preg_split('/\s+-\s+/', $normalized) ?: [])
            ->map(fn (string $part): string => trim($part))
            ->filter()
            ->values();

        $target = $parts->last() ?: $normalized;

        if ($parts->count() > 1 && ! preg_match('/[a-z]/i', $target) && preg_match('/([a-z]+\s+)\d{1,2}/i', $parts->first(), $match)) {
            $target = $match[1].$target;
        }

        try {
            return Carbon::parse($target);
        } catch (\Throwable) {
            return null;
        }
    }

    private function uniqueSlug(string $base): string
    {
        $base = trim($base, '/') ?: 'announcements/announcement';
        $slug = $base;
        $counter = 2;

        while (Page::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @return array<int, string>
     */
    private function pageTokens(Page $page): array
    {
        return $this->tokens(collect([
            $page->title,
            $page->slug,
            $page->intro,
            json_encode($page->content_blocks),
        ])->filter()->implode(' '));
    }

    /**
     * @return array<int, string>
     */
    private function tokens(string $value): array
    {
        return str($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9\s]+/', ' ')
            ->explode(' ')
            ->map(fn (string $token): string => trim($token))
            ->filter(fn (string $token): bool => strlen($token) >= 3 && ! in_array($token, [
                'and', 'the', 'for', 'with', 'this', 'that', 'from', 'your', 'you', 'are',
            ], true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $slideTokens
     * @param  array<int, string>  $pageTokens
     */
    private function score(array $slideTokens, array $pageTokens): float
    {
        if ($slideTokens === [] || $pageTokens === []) {
            return 0.0;
        }

        $intersection = count(array_intersect($slideTokens, $pageTokens));
        $minimum = min(count($slideTokens), count($pageTokens));

        return $minimum > 0 ? $intersection / $minimum : 0.0;
    }
}

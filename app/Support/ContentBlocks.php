<?php

namespace App\Support;

use App\Models\FileDocument;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContentBlocks
{
    public const RELATED_CONTENT_TYPE_BOTH = 'both';

    public const RELATED_CONTENT_TYPE_PAGES = 'pages';

    public const RELATED_CONTENT_TYPE_FILES = 'files';

    public const RELATED_CONTENT_MODE_FEATURED = 'featured';

    public const RELATED_CONTENT_MODE_ALL = 'all_live';

    public const RELATED_CONTENT_MODE_NEWEST = 'newest_live';

    public const RELATED_CONTENT_DEFAULT_LIMIT = 6;

    public const YOUTUBE_FEED_DEFAULT_LIMIT = 12;

    public const DEFAULT_PAGE_CARD_IMAGE_PATH = 'images/page-card-default.svg';

    public static function prepare(?array $blocks, ?SiteSetting $settings = null, ?Page $page = null): array
    {
        return collect($blocks ?? [])
            ->map(function (array $block) use ($settings, $page): array {
                $type = $block['type'] ?? null;
                $data = $block['data'] ?? [];

                if ($type === 'image_text') {
                    $data['image_url'] = self::imageUrl($data['image_path'] ?? null);
                }

                if ($type === 'info_strip') {
                    $data['items'] = self::infoStripItems($data['items'] ?? [], $settings);
                }

                if ($type === 'related_content') {
                    $data = self::prepareRelatedContentBlock($page, $data);
                }

                if ($type === 'youtube_feed') {
                    $data = self::prepareYoutubeFeedBlock($data);
                }

                return [
                    'type' => $type,
                    'data' => $data,
                ];
            })
            ->filter(fn (array $block): bool => filled($block['type']))
            ->filter(fn (array $block): bool => self::hasRenderableContent($block))
            ->filter(fn (array $block): bool => $block['type'] !== 'info_strip' || filled($block['data']['items'] ?? []))
            ->filter(fn (array $block): bool => $block['type'] !== 'related_content' || (bool) ($block['data']['is_visible'] ?? true))
            ->filter(fn (array $block): bool => $block['type'] !== 'related_content' || filled($block['data']['items'] ?? []))
            ->filter(fn (array $block): bool => $block['type'] !== 'youtube_feed' || filled($block['data']['videos'] ?? []) || filled($block['data']['channel_url'] ?? null))
            ->values()
            ->all();
    }

    public static function relatedContentListing(string $slug): ?array
    {
        $slug = trim($slug, '/');

        if (Page::query()->where('slug', $slug)->exists()) {
            return null;
        }

        $segments = explode('/', $slug);

        if (count($segments) < 2) {
            return null;
        }

        $listingSlug = array_pop($segments);
        $parentSlug = implode('/', $segments);

        $page = Page::query()
            ->where('slug', $parentSlug)
            ->active()
            ->first();

        if (! $page || $page->isRedirect()) {
            return null;
        }

        foreach ($page->content_blocks ?? [] as $block) {
            if (($block['type'] ?? null) !== 'related_content') {
                continue;
            }

            $data = self::relatedContentDefaults($block['data'] ?? []);

            if (! (bool) ($data['is_visible'] ?? true)) {
                continue;
            }

            if (! hash_equals(self::relatedListingSlug($data), $listingSlug)) {
                continue;
            }

            $data = self::prepareRelatedContentBlock($page, $data, allItems: true);

            if (blank($data['items'] ?? [])) {
                return null;
            }

            return [
                'page' => $page,
                'data' => $data,
            ];
        }

        return null;
    }

    public static function relatedListingSlug(array $data): string
    {
        return Str::slug($data['listing_slug'] ?? null)
            ?: Str::slug($data['heading'] ?? null)
            ?: 'child-cards';
    }

    private static function prepareRelatedContentBlock(?Page $page, array $data, bool $allItems = false): array
    {
        $data = self::relatedContentDefaults($data);
        $limit = $allItems ? null : self::relatedContentLimit($data);
        $items = self::relatedContentItems($page, $data);
        $hasMore = $limit !== null && $items->count() > $limit;

        $data['items'] = ($limit === null ? $items : $items->take($limit))->values()->all();
        $data['has_more'] = $hasMore;
        $data['listing_slug'] = self::relatedListingSlug($data);
        $data['view_more_url'] = $hasMore ? self::relatedListingUrl($page, $data) : null;

        return $data;
    }

    private static function relatedContentDefaults(array $data): array
    {
        $data['is_visible'] = $data['is_visible'] ?? true;
        $data['heading'] = $data['heading'] ?? null;
        $data['intro'] = $data['intro'] ?? null;
        $data['background'] = $data['background'] ?? 'white';
        $data['content_type'] = in_array($data['content_type'] ?? null, self::relatedContentTypeOptions(), true)
            ? $data['content_type']
            : self::RELATED_CONTENT_TYPE_BOTH;
        $data['display_mode'] = in_array($data['display_mode'] ?? null, self::relatedContentModeOptions(), true)
            ? $data['display_mode']
            : self::RELATED_CONTENT_MODE_FEATURED;
        $data['item_limit'] = self::relatedContentLimit($data);
        $data['link_label'] = $data['link_label'] ?? 'View more';
        $data['file_categories'] = self::normalizeStringList($data['file_categories'] ?? []);

        return $data;
    }

    private static function relatedContentItems(?Page $page, array $data): Collection
    {
        if (! $page?->getKey()) {
            return collect();
        }

        $items = collect();

        if (in_array($data['content_type'], [self::RELATED_CONTENT_TYPE_BOTH, self::RELATED_CONTENT_TYPE_PAGES], true)) {
            $items = $items->merge(self::relatedPageItems($page, $data));
        }

        if (in_array($data['content_type'], [self::RELATED_CONTENT_TYPE_BOTH, self::RELATED_CONTENT_TYPE_FILES], true)) {
            $items = $items->merge(self::relatedFileItems($page, $data));
        }

        if ($data['display_mode'] === self::RELATED_CONTENT_MODE_NEWEST) {
            return $items
                ->sort(function (array $first, array $second): int {
                    $dateComparison = strcmp($second['sort_date'] ?? '', $first['sort_date'] ?? '');

                    return $dateComparison !== 0
                        ? $dateComparison
                        : strcasecmp($first['title'], $second['title']);
                })
                ->values();
        }

        return self::sortRelatedContentItems($items);
    }

    private static function sortRelatedContentItems(Collection $items): Collection
    {
        return $items
            ->map(function (array $item): array {
                $item['sort_random'] = ($item['kind'] ?? null) === 'page'
                    ? random_int(0, PHP_INT_MAX)
                    : PHP_INT_MAX;

                return $item;
            })
            ->sortBy([
                ['sort_group', 'asc'],
                ['sort_order', 'asc'],
                ['sort_random', 'asc'],
                ['title', 'asc'],
            ])
            ->map(function (array $item): array {
                unset($item['sort_random']);

                return $item;
            })
            ->values();
    }

    private static function relatedPageItems(Page $page, array $data): Collection
    {
        $now = now();

        return $page->childPages()
            ->active()
            ->where('is_redirect', false)
            ->when($data['display_mode'] === self::RELATED_CONTENT_MODE_FEATURED, fn ($query) => $query
                ->where(fn ($query) => $query->whereNull('featured_at')->orWhere('featured_at', '<=', $now))
                ->where(fn ($query) => $query->whereNull('feature_expires_at')->orWhere('feature_expires_at', '>=', $now)))
            ->get()
            ->map(fn (Page $child): array => [
                'kind' => 'page',
                'type' => $child->hero_label ?: 'Page',
                'title' => $child->title,
                'summary' => $child->intro ?: $child->message,
                'image_url' => self::relatedPageImageUrl($child),
                'url' => $child->publicUrl(),
                'sort_group' => 0,
                'sort_order' => $child->sort_order ?? 0,
                'sort_date' => ($child->featured_at ?? $child->publish_at ?? $child->updated_at)?->toDateTimeString(),
            ]);
    }

    private static function relatedPageImageUrl(Page $page): string
    {
        return self::imageUrl($page->card_image_path)
            ?? self::imageUrl($page->hero_image_path)
            ?? asset(self::DEFAULT_PAGE_CARD_IMAGE_PATH);
    }

    private static function relatedFileItems(Page $page, array $data): Collection
    {
        $now = now();
        $categories = self::normalizeStringList($data['file_categories'] ?? []);

        return $page->fileDocuments()
            ->with('currentVersion')
            ->where('is_published', true)
            ->where('visibility', FileDocument::VISIBILITY_PUBLIC)
            ->whereNotNull('current_version_id')
            ->where(fn ($query) => $query->whereNull('publish_at')->orWhere('publish_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', $now))
            ->when($categories !== [], fn ($query) => $query->whereIn('category', $categories))
            ->get()
            ->map(fn (FileDocument $document): array => [
                'kind' => 'file',
                'type' => $document->category ?: 'File',
                'title' => $document->title,
                'summary' => $document->description ?: self::excerpt($document->content),
                'image_url' => $document->cardImageUrl(),
                'url' => $document->publicUrl(),
                'sort_group' => 1,
                'sort_order' => 0,
                'sort_date' => ($document->publish_at ?? $document->updated_at)?->toDateTimeString(),
            ]);
    }

    private static function relatedListingUrl(?Page $page, array $data): ?string
    {
        if (! $page?->getKey()) {
            return null;
        }

        $slug = trim((string) $page->slug, '/').'/'.self::relatedListingSlug($data);

        if (Page::query()->where('slug', $slug)->exists()) {
            return null;
        }

        return url('/'.$slug);
    }

    private static function relatedContentLimit(array $data): int
    {
        return min(50, max(1, (int) ($data['item_limit'] ?? self::RELATED_CONTENT_DEFAULT_LIMIT)));
    }

    private static function prepareYoutubeFeedBlock(array $data): array
    {
        $channelUrl = self::youtubeVideosUrl($data['youtube_channel_url'] ?? null);
        $feedUrl = filled($data['youtube_feed_url'] ?? null)
            ? trim((string) $data['youtube_feed_url'])
            : null;
        $limit = self::youtubeFeedLimit($data);

        $data['channel_url'] = $channelUrl;
        $data['youtube_feed_url'] = $feedUrl;
        $data['youtube_link_label'] = filled($data['youtube_link_label'] ?? null)
            ? (string) $data['youtube_link_label']
            : 'View more on YouTube';
        $data['item_limit'] = $limit;
        $data['videos'] = filled($feedUrl)
            ? app(YoutubeSermonFeed::class)->latest(limit: $limit, feedUrl: $feedUrl)
            : [];

        return $data;
    }

    private static function youtubeFeedLimit(array $data): int
    {
        return min(50, max(1, (int) ($data['item_limit'] ?? self::YOUTUBE_FEED_DEFAULT_LIMIT)));
    }

    private static function youtubeVideosUrl(mixed $channelUrl): ?string
    {
        if (blank($channelUrl)) {
            return null;
        }

        $channelUrl = rtrim((string) $channelUrl, '/');

        return str_ends_with($channelUrl, '/videos') ? $channelUrl : "{$channelUrl}/videos";
    }

    private static function relatedContentTypeOptions(): array
    {
        return [
            self::RELATED_CONTENT_TYPE_BOTH,
            self::RELATED_CONTENT_TYPE_PAGES,
            self::RELATED_CONTENT_TYPE_FILES,
        ];
    }

    private static function relatedContentModeOptions(): array
    {
        return [
            self::RELATED_CONTENT_MODE_FEATURED,
            self::RELATED_CONTENT_MODE_ALL,
            self::RELATED_CONTENT_MODE_NEWEST,
        ];
    }

    private static function normalizeStringList(mixed $value): array
    {
        return collect(is_array($value) ? $value : [$value])
            ->map(fn (mixed $item): string => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private static function excerpt(?string $value): ?string
    {
        $text = trim(html_entity_decode(strip_tags($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $text === '' ? null : Str::limit($text, 180);
    }

    public static function imageUrl(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = collect($path)->first();
        }

        if (! $path) {
            return null;
        }

        $path = (string) $path;

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    private static function infoStripItems(array $items, ?SiteSetting $settings): array
    {
        return collect($items)
            ->map(function (array $item) use ($settings): array {
                $source = $item['source'] ?? 'custom';
                $value = $item['value'] ?? null;

                if ($source === 'sunday_service_times') {
                    $value = $settings?->sunday_service_times ?: $value;
                }

                if ($source === 'office_hours') {
                    $value = $settings?->office_hours ?: $value;
                }

                if ($source === 'address') {
                    $value = $settings?->address ?: $value;
                }

                return [
                    'label' => $item['label'] ?? null,
                    'value' => $value,
                ];
            })
            ->filter(fn (array $item): bool => filled($item['label'] ?? null) && filled($item['value'] ?? null))
            ->values()
            ->all();
    }

    private static function hasRenderableContent(array $block): bool
    {
        $data = $block['data'] ?? [];

        return match ($block['type'] ?? null) {
            'text' => self::hasText($data['eyebrow'] ?? null)
                || self::hasText($data['heading'] ?? null)
                || self::hasText($data['body'] ?? null),
            'image_text' => filled($data['image_url'] ?? null)
                || self::hasText($data['eyebrow'] ?? null)
                || self::hasText($data['heading'] ?? null)
                || self::hasText($data['body'] ?? null)
                || (filled($data['button_label'] ?? null) && filled($data['button_url'] ?? null)),
            'process_steps' => filled($data['steps'] ?? []),
            'cta' => self::hasText($data['eyebrow'] ?? null)
                || self::hasText($data['heading'] ?? null)
                || self::hasText($data['body'] ?? null)
                || (filled($data['button_label'] ?? null) && filled($data['button_url'] ?? null)),
            'link_cards' => filled($data['cards'] ?? []),
            'embed' => self::hasText($data['heading'] ?? null) || filled($data['embed_code'] ?? null),
            'code' => filled($data['code'] ?? null),
            'info_strip', 'related_content', 'youtube_feed' => true,
            'announcements_bar' => false,
            default => true,
        };
    }

    private static function hasText(?string $value): bool
    {
        $text = html_entity_decode(strip_tags($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        return trim($text) !== '';
    }
}

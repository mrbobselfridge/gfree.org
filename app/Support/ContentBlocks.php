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
    private const RELATED_CONTENT_SUMMARY_LIMIT = 180;

    public const RELATED_CONTENT_TYPE_BOTH = 'both';

    public const RELATED_CONTENT_TYPE_PAGES = 'pages';

    public const RELATED_CONTENT_TYPE_FILES = 'files';

    public const RELATED_CONTENT_MODE_FEATURED = 'featured';

    public const RELATED_CONTENT_MODE_ALL = 'all_live';

    public const RELATED_CONTENT_MODE_NEWEST = 'newest_live';

    public const RELATED_CONTENT_SORT_ORDER_RANDOM = 'order_random';

    public const RELATED_CONTENT_SORT_FEATURED_PUBLISHED_ORDER_RANDOM = 'featured_published_order_random';

    public const RELATED_CONTENT_SORT_PUBLISHED_ORDER_RANDOM = 'published_order_random';

    public const RELATED_CONTENT_SORT_TITLE_ASC = 'title_asc';

    public const RELATED_CONTENT_SORT_TITLE_DESC = 'title_desc';

    public const RELATED_CONTENT_SORT_UPDATED_DESC = 'updated_desc';

    public const RELATED_CONTENT_SORT_CREATED_DESC = 'created_desc';

    public const RELATED_CONTENT_SORT_CREATED_ASC = 'created_asc';

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

    /**
     * @return array<string, string>
     */
    public static function relatedContentSortOptions(): array
    {
        return [
            self::RELATED_CONTENT_SORT_ORDER_RANDOM => 'Order ASC, random',
            self::RELATED_CONTENT_SORT_FEATURED_PUBLISHED_ORDER_RANDOM => 'Featured DESC, Published DESC, order ASC, random',
            self::RELATED_CONTENT_SORT_PUBLISHED_ORDER_RANDOM => 'Published DESC, order ASC, random',
            self::RELATED_CONTENT_SORT_TITLE_ASC => "Title A-Z (A's first)",
            self::RELATED_CONTENT_SORT_TITLE_DESC => "Title Z-A (Z's First)",
            self::RELATED_CONTENT_SORT_UPDATED_DESC => 'Updated DESC (last updated first)',
            self::RELATED_CONTENT_SORT_CREATED_DESC => 'Created DESC (newest first)',
            self::RELATED_CONTENT_SORT_CREATED_ASC => 'Created ASC (oldest first)',
        ];
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
        $data['sort_preset'] = array_key_exists($data['sort_preset'] ?? null, self::relatedContentSortOptions())
            ? $data['sort_preset']
            : self::defaultRelatedContentSortPreset($data);
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

        return self::sortRelatedContentItems($items, $data['sort_preset']);
    }

    private static function sortRelatedContentItems(Collection $items, string $sortPreset): Collection
    {
        return $items
            ->map(function (array $item) use ($sortPreset): array {
                $item['sort_random'] = self::relatedContentSortUsesRandom($sortPreset)
                    ? random_int(0, PHP_INT_MAX)
                    : 0;

                return $item;
            })
            ->sort(fn (array $first, array $second): int => self::compareRelatedContentItems($first, $second, $sortPreset))
            ->map(function (array $item): array {
                unset($item['sort_random']);

                return $item;
            })
            ->values();
    }

    private static function compareRelatedContentItems(array $first, array $second, string $sortPreset): int
    {
        return match ($sortPreset) {
            self::RELATED_CONTENT_SORT_FEATURED_PUBLISHED_ORDER_RANDOM => self::compareDateDesc($first, $second, 'featured_at')
                ?: self::compareDateDesc($first, $second, 'publish_at')
                ?: self::compareIntAsc($first, $second, 'sort_order')
                ?: self::compareRandom($first, $second),
            self::RELATED_CONTENT_SORT_PUBLISHED_ORDER_RANDOM => self::compareDateDesc($first, $second, 'publish_at')
                ?: self::compareIntAsc($first, $second, 'sort_order')
                ?: self::compareRandom($first, $second),
            self::RELATED_CONTENT_SORT_TITLE_ASC => self::compareTitleAsc($first, $second),
            self::RELATED_CONTENT_SORT_TITLE_DESC => self::compareTitleDesc($first, $second),
            self::RELATED_CONTENT_SORT_UPDATED_DESC => self::compareDateDesc($first, $second, 'updated_at')
                ?: self::compareTitleAsc($first, $second),
            self::RELATED_CONTENT_SORT_CREATED_DESC => self::compareDateDesc($first, $second, 'created_at')
                ?: self::compareTitleAsc($first, $second),
            self::RELATED_CONTENT_SORT_CREATED_ASC => self::compareDateAsc($first, $second, 'created_at')
                ?: self::compareTitleAsc($first, $second),
            default => self::compareIntAsc($first, $second, 'sort_order')
                ?: self::compareRandom($first, $second),
        };
    }

    private static function compareDateDesc(array $first, array $second, string $field): int
    {
        return self::compareNullableDates($first[$field] ?? null, $second[$field] ?? null, descending: true);
    }

    private static function compareDateAsc(array $first, array $second, string $field): int
    {
        return self::compareNullableDates($first[$field] ?? null, $second[$field] ?? null, descending: false);
    }

    private static function compareNullableDates(?string $first, ?string $second, bool $descending): int
    {
        if ($first === $second) {
            return 0;
        }

        if ($first === null || $first === '') {
            return 1;
        }

        if ($second === null || $second === '') {
            return -1;
        }

        return $descending ? strcmp($second, $first) : strcmp($first, $second);
    }

    private static function compareIntAsc(array $first, array $second, string $field): int
    {
        return ((int) ($first[$field] ?? 0)) <=> ((int) ($second[$field] ?? 0));
    }

    private static function compareRandom(array $first, array $second): int
    {
        return ((int) ($first['sort_random'] ?? 0)) <=> ((int) ($second['sort_random'] ?? 0));
    }

    private static function compareTitleAsc(array $first, array $second): int
    {
        return strcasecmp((string) ($first['title'] ?? ''), (string) ($second['title'] ?? ''));
    }

    private static function compareTitleDesc(array $first, array $second): int
    {
        return strcasecmp((string) ($second['title'] ?? ''), (string) ($first['title'] ?? ''));
    }

    private static function relatedContentSortUsesRandom(?string $sortPreset): bool
    {
        return in_array($sortPreset, [
            self::RELATED_CONTENT_SORT_ORDER_RANDOM,
            self::RELATED_CONTENT_SORT_FEATURED_PUBLISHED_ORDER_RANDOM,
            self::RELATED_CONTENT_SORT_PUBLISHED_ORDER_RANDOM,
        ], true);
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
                'featured_at' => self::sortableDate($child->featured_at),
                'publish_at' => self::sortableDate($child->publish_at),
                'updated_at' => self::sortableDate($child->updated_at),
                'created_at' => self::sortableDate($child->created_at),
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
            ->map(fn (FileDocument $document): array => self::relatedFileItem($document));
    }

    /**
     * @return array<string, mixed>
     */
    private static function relatedFileItem(FileDocument $document): array
    {
        $optionalContentText = self::plainText($document->content);

        return [
            'kind' => 'file',
            'type' => $document->category ?: 'File',
            'title' => $document->title,
            'summary' => $document->description ?: self::excerpt($document->content),
            'image_url' => $document->cardImageUrl(),
            'url' => $document->publicUrl(),
            'optional_content_html' => filled($optionalContentText) ? self::basicHtml($document->content) : null,
            'has_more_content' => Str::length($optionalContentText) > self::RELATED_CONTENT_SUMMARY_LIMIT,
            'sort_group' => 1,
            'sort_order' => $document->sort_order ?? 0,
            'featured_at' => null,
            'publish_at' => self::sortableDate($document->publish_at),
            'updated_at' => self::sortableDate($document->updated_at),
            'created_at' => self::sortableDate($document->created_at),
            'sort_date' => ($document->publish_at ?? $document->updated_at)?->toDateTimeString(),
        ];
    }

    private static function sortableDate(mixed $date): ?string
    {
        return $date?->toDateTimeString();
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

    private static function defaultRelatedContentSortPreset(array $data): string
    {
        return ($data['display_mode'] ?? null) === self::RELATED_CONTENT_MODE_NEWEST
            ? self::RELATED_CONTENT_SORT_PUBLISHED_ORDER_RANDOM
            : self::RELATED_CONTENT_SORT_ORDER_RANDOM;
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
        $text = self::plainText($value);

        return $text === '' ? null : Str::limit($text, self::RELATED_CONTENT_SUMMARY_LIMIT);
    }

    private static function plainText(?string $value): string
    {
        return trim(html_entity_decode(strip_tags($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private static function basicHtml(?string $value): string
    {
        $html = (string) $value;
        $html = preg_replace('/<(script|style|iframe|object|embed|form|input|button|textarea|select|option|meta|link)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $html = preg_replace('/<(script|style|iframe|object|embed|form|input|button|textarea|select|option|meta|link)\b[^>]*\/?>/i', '', $html) ?? $html;
        $html = strip_tags($html, '<h1><h2><h3><h4><p><div><br><ul><ol><li><strong><b><em><i><a><table><thead><tbody><tr><th><td>');

        return preg_replace_callback('/<([a-z0-9]+)(\s[^>]*)?>/i', function (array $match): string {
            $tag = strtolower($match[1]);

            if ($tag !== 'a') {
                return "<{$tag}>";
            }

            $attributes = $match[2] ?? '';

            if (! preg_match('/\bhref\s*=\s*([\'"])(.*?)\1/i', $attributes, $hrefMatch)) {
                return '<a>';
            }

            $href = trim(html_entity_decode($hrefMatch[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if (! self::isAllowedHref($href)) {
                return '<a>';
            }

            return '<a href="'.e($href).'">';
        }, $html) ?? '';
    }

    private static function isAllowedHref(string $href): bool
    {
        return str_starts_with($href, '/')
            || str_starts_with($href, '#')
            || preg_match('/^(https?:|mailto:|tel:)/i', $href) === 1;
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

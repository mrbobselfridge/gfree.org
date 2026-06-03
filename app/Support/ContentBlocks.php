<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ContentBlocks
{
    public const FEATURED_ANNOUNCEMENT_LIMIT = 9;

    public static function prepare(?array $blocks, ?SiteSetting $settings = null, ?Collection $updates = null): array
    {
        return collect($blocks ?? [])
            ->map(function (array $block) use ($settings, $updates): array {
                $type = $block['type'] ?? null;
                $data = $block['data'] ?? [];

                if ($type === 'image_text') {
                    $data['image_url'] = self::imageUrl($data['image_path'] ?? null);
                }

                if ($type === 'info_strip') {
                    $data['items'] = self::infoStripItems($data['items'] ?? [], $settings);
                }

                if ($type === 'announcements_bar') {
                    $data['updates'] = $updates ?? self::featuredAnnouncementUpdates();
                    $data['is_visible'] = $data['is_visible'] ?? true;
                    $data['heading'] = $data['heading'] ?? 'Latest at gFree';
                    $data['link_label'] = $data['link_label'] ?? 'View all';
                    $data['link_url'] = $data['link_url'] ?? '/announcements';
                    $data['background'] = $data['background'] ?? 'white';
                }

                return [
                    'type' => $type,
                    'data' => $data,
                ];
            })
            ->filter(fn (array $block): bool => filled($block['type']))
            ->filter(fn (array $block): bool => self::hasRenderableContent($block))
            ->filter(fn (array $block): bool => $block['type'] !== 'info_strip' || filled($block['data']['items'] ?? []))
            ->filter(fn (array $block): bool => $block['type'] !== 'announcements_bar' || (bool) ($block['data']['is_visible'] ?? true))
            ->filter(fn (array $block): bool => $block['type'] !== 'announcements_bar' || filled($block['data']['updates'] ?? []))
            ->values()
            ->all();
    }

    public static function featuredAnnouncementUpdates(): Collection
    {
        $now = now();

        return Announcement::query()
            ->where('is_published', true)
            ->where('is_featured', true)
            ->where(fn ($query) => $query->whereNull('publish_at')->orWhere('publish_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', $now))
            ->where(fn ($query) => $query->whereNull('featured_at')->orWhere('featured_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('feature_expires_at')->orWhere('feature_expires_at', '>=', $now))
            ->orderByRaw('COALESCE(featured_at, publish_at, created_at) DESC')
            ->latest()
            ->limit(self::FEATURED_ANNOUNCEMENT_LIMIT)
            ->get()
            ->map(fn (Announcement $announcement) => [
                'type' => $announcement->is_featured ? 'Featured' : 'Announcement',
                'title' => $announcement->title,
                'summary' => $announcement->summary,
                'image_url' => self::imageUrl($announcement->image_path),
                'url' => url('/announcements/'.$announcement->slug),
            ]);
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
            'info_strip', 'announcements_bar' => true,
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

<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\NavigationLink;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class PageController extends Controller
{
    public function __invoke(string $slug): View
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $settings = SiteSetting::query()->first();
        $defaults = config('gfree.homepage');
        $now = now();

        $navigationLinks = NavigationLink::query()
            ->topLevelHeader()
            ->limit(10)
            ->get();

        $announcements = Announcement::query()
            ->where('is_published', true)
            ->where('is_featured', true)
            ->where(fn ($query) => $query->whereNull('publish_at')->orWhere('publish_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', $now))
            ->where(fn ($query) => $query->whereNull('featured_at')->orWhere('featured_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('feature_expires_at')->orWhere('feature_expires_at', '>=', $now))
            ->orderByRaw('COALESCE(featured_at, publish_at, created_at) DESC')
            ->latest()
            ->limit(3)
            ->get();

        return view('pages.show', [
            'settings' => $settings,
            'page' => $page,
            'contentBlocks' => $this->contentBlocks($page, $settings, $this->announcementUpdates($announcements)),
            'heroImageUrl' => $this->imageUrl($page->hero_image_path),
            'headerLinks' => $navigationLinks->isNotEmpty() ? $navigationLinks : collect($defaults['navigation']),
            'socialLinks' => $this->socialLinks($settings),
        ]);
    }

    private function contentBlocks(Page $page, ?SiteSetting $settings, $updates): array
    {
        return collect($page->content_blocks ?? [])
            ->map(function (array $block) use ($settings, $updates): array {
                $type = $block['type'] ?? null;
                $data = $block['data'] ?? [];

                if ($type === 'image_text') {
                    $data['image_url'] = $this->imageUrl($data['image_path'] ?? null);
                }

                if ($type === 'info_strip') {
                    $data['items'] = $this->infoStripItems($data['items'] ?? [], $settings);
                }

                if ($type === 'announcements_bar') {
                    $data['updates'] = $updates;
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
            ->filter(fn (array $block): bool => $block['type'] !== 'info_strip' || filled($block['data']['items'] ?? []))
            ->filter(fn (array $block): bool => $block['type'] !== 'announcements_bar' || (bool) ($block['data']['is_visible'] ?? true))
            ->filter(fn (array $block): bool => $block['type'] !== 'announcements_bar' || filled($block['data']['updates'] ?? []))
            ->values()
            ->all();
    }

    private function announcementUpdates($announcements)
    {
        return $announcements->map(fn (Announcement $announcement) => [
            'type' => $announcement->is_featured ? 'Featured' : 'Announcement',
            'title' => $announcement->title,
            'summary' => $announcement->summary,
            'image_url' => $this->imageUrl($announcement->image_path),
            'url' => url('/announcements/'.$announcement->slug),
        ]);
    }

    private function infoStripItems(array $items, ?SiteSetting $settings): array
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

    private function socialLinks(?SiteSetting $settings)
    {
        return collect([
            ['label' => 'Facebook', 'url' => $settings?->facebook_url],
            ['label' => 'Instagram', 'url' => $settings?->instagram_url],
            ['label' => 'YouTube', 'url' => $settings?->youtube_url],
        ])->filter(fn (array $link) => filled($link['url']));
    }

    private function imageUrl(mixed $path): ?string
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
}

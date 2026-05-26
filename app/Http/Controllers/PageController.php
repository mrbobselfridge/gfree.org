<?php

namespace App\Http\Controllers;

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

        $navigationLinks = NavigationLink::query()
            ->active()
            ->where('location', 'header')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->limit(5)
            ->get();

        return view('pages.show', [
            'settings' => $settings,
            'page' => $page,
            'contentBlocks' => $this->contentBlocks($page),
            'heroImageUrl' => $this->imageUrl($page->hero_image_path),
            'headerLinks' => $navigationLinks->isNotEmpty() ? $navigationLinks : collect($defaults['navigation']),
            'socialLinks' => $this->socialLinks($settings),
        ]);
    }

    private function contentBlocks(Page $page): array
    {
        return collect($page->content_blocks ?? [])
            ->map(function (array $block): array {
                $type = $block['type'] ?? null;
                $data = $block['data'] ?? [];

                if ($type === 'image_text') {
                    $data['image_url'] = $this->imageUrl($data['image_path'] ?? null);
                }

                return [
                    'type' => $type,
                    'data' => $data,
                ];
            })
            ->filter(fn (array $block): bool => filled($block['type']))
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

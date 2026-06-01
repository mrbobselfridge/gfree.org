<?php

namespace App\Http\Controllers;

use App\Models\Ministry;
use App\Models\NavigationLink;
use App\Models\SiteSetting;
use App\Support\ContentBlocks;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class MinistryController extends Controller
{
    public function index(): View
    {
        $ministries = Ministry::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Ministry $ministry): Ministry {
                $ministry->image_url = $this->imageUrl($ministry->card_image_path ?: $ministry->hero_image_path);

                return $ministry;
            });

        return view('ministries.index', [
            ...$this->sharedViewData(),
            'ministries' => $ministries,
            'hero' => $this->listingHero('ministry', [
                'title' => 'Find your place.',
                'subtitle' => 'Explore ministries, groups, and next steps at gFree Church.',
            ]),
        ]);
    }

    public function show(string $slug): View
    {
        $ministry = Ministry::query()
            ->where('is_published', true)
            ->where('slug', $slug)
            ->firstOrFail();

        return view('ministries.show', [
            ...$this->sharedViewData(),
            'ministry' => $ministry,
            'contentBlocks' => ContentBlocks::prepare($ministry->content_blocks, SiteSetting::query()->first()),
            'heroImageUrl' => $this->imageUrl($ministry->hero_image_path) ?: $this->listingImageUrl('ministry'),
            'detailItems' => $this->detailItems($ministry),
        ]);
    }

    private function detailItems(Ministry $ministry)
    {
        return collect([
            ['label' => 'When', 'value' => $ministry->meeting_time],
            ['label' => 'Where', 'value' => $ministry->location],
            ['label' => 'Leader', 'value' => $ministry->leader_name],
            ['label' => 'Phone', 'value' => $ministry->leader_phone],
        ])->filter(fn (array $item) => filled($item['value']));
    }

    private function sharedViewData(): array
    {
        $settings = SiteSetting::query()->first();
        $defaults = config('gfree.homepage');

        $navigationLinks = NavigationLink::query()
            ->topLevelHeader()
            ->limit(10)
            ->get();

        return [
            'settings' => $settings,
            'headerLinks' => $navigationLinks->isNotEmpty() ? $navigationLinks : collect($defaults['navigation']),
            'socialLinks' => $this->socialLinks($settings),
        ];
    }

    private function listingHero(string $prefix, array $defaults): array
    {
        $settings = SiteSetting::query()->first();

        return [
            'small_label' => data_get($settings, "{$prefix}_small_label"),
            'title' => data_get($settings, "{$prefix}_title") ?: $defaults['title'],
            'subtitle' => data_get($settings, "{$prefix}_subtitle") ?: ($defaults['subtitle'] ?? null),
            'image_url' => $this->imageUrl(data_get($settings, "{$prefix}_image_path")),
        ];
    }

    private function listingImageUrl(string $prefix): ?string
    {
        $settings = SiteSetting::query()->first();

        return $this->imageUrl(data_get($settings, "{$prefix}_image_path"));
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

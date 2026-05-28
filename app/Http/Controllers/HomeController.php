<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\HomepageContent;
use App\Models\HomepageBanner;
use App\Models\Ministry;
use App\Models\NavigationLink;
use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $settings = SiteSetting::query()->first();
        $homepageContent = HomepageContent::query()->first();
        $defaults = config('gfree.homepage');
        $now = now();

        $heroBanners = HomepageBanner::query()
            ->where('is_published', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->inRandomOrder()
            ->get();

        $navigationLinks = NavigationLink::query()
            ->active()
            ->where('location', 'header')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->limit(5)
            ->get();

        $ministries = Ministry::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->limit(3)
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

        return view('home', [
            'settings' => $settings,
            'theme' => $defaults['theme'],
            'headerLinks' => $navigationLinks->isNotEmpty() ? $navigationLinks : collect($defaults['navigation']),
            'hero' => $this->hero($defaults['hero'], $heroBanners->first()),
            'heroSlides' => $this->heroSlides($defaults['hero'], $heroBanners),
            'serviceDetails' => $this->serviceDetails($defaults['service_details'], $settings),
            'contentBlocks' => $this->contentBlocks($homepageContent, $defaults, $settings, $ministries),
            'updates' => $announcements->isNotEmpty() ? $this->announcementUpdates($announcements) : collect($defaults['updates']),
            'socialLinks' => $this->socialLinks($settings),
        ]);
    }

    private function heroSlides(array $defaults, $banners)
    {
        if ($banners->isEmpty()) {
            return collect([$defaults]);
        }

        return $banners->map(fn (HomepageBanner $banner): array => $this->hero($defaults, $banner))->values();
    }

    private function hero(array $defaults, ?HomepageBanner $banner): array
    {
        if (! $banner) {
            return $defaults;
        }

        return [
            'eyebrow' => $banner->eyebrow ?: $defaults['eyebrow'],
            'title' => $banner->title,
            'subtitle' => $banner->subtitle,
            'image_url' => $this->imageUrl($banner->image_path) ?: $defaults['image_url'],
            'primary_label' => $banner->button_label,
            'primary_url' => $banner->button_url ?: $defaults['primary_url'],
            'secondary_label' => $banner->secondary_button_label,
            'secondary_url' => $banner->secondary_button_url ?: $defaults['secondary_url'],
        ];
    }

    private function serviceDetails(array $defaults, ?SiteSetting $settings): array
    {
        if (! $settings) {
            return $defaults;
        }

        $defaults[0]['value'] = $settings->sunday_service_times ?: $defaults[0]['value'];
        $defaults[1]['value'] = $settings->address ?: $defaults[1]['value'];

        return $defaults;
    }

    private function ministrySteps($ministries)
    {
        return $ministries->values()->map(fn (Ministry $ministry, int $index) => [
            'number' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
            'title' => $ministry->name,
            'summary' => $ministry->short_summary ?: $ministry->description,
            'url' => $ministry->one_church_url ?: url('/ministry/'.$ministry->slug),
        ]);
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

    private function feature(array $defaults, ?SiteSetting $settings, ?HomepageContent $content): array
    {
        $featureUrl = $content?->feature_url ?: $defaults['url'];

        if ($settings?->one_church_url && (blank($featureUrl) || $featureUrl === '#')) {
            $featureUrl = $settings->one_church_url;
        }

        return [
            'eyebrow' => $content?->feature_eyebrow ?: $defaults['eyebrow'],
            'title' => $content?->feature_title ?: $defaults['title'],
            'body' => $content?->feature_body ?: $defaults['body'],
            'label' => $content?->feature_label ?: $defaults['label'],
            'url' => $featureUrl,
        ];
    }

    private function contentBlocks(?HomepageContent $content, array $defaults, ?SiteSetting $settings, $ministries): array
    {
        $blocks = $content?->content_blocks;

        if (blank($blocks)) {
            $blocks = $this->defaultHomepageBlocks($defaults, $settings, $ministries);
        }

        return collect($blocks)
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

    private function defaultHomepageBlocks(array $defaults, ?SiteSetting $settings, $ministries): array
    {
        $nextSteps = $ministries->isNotEmpty() ? $this->ministrySteps($ministries)->all() : ($defaults['next_steps'] ?? []);
        $feature = $this->feature($defaults['feature'], $settings, null);

        return [
            [
                'type' => 'text',
                'data' => [
                    'eyebrow' => $defaults['intro']['eyebrow'] ?? null,
                    'heading' => $defaults['intro']['title'] ?? null,
                    'body' => '<p>'.($defaults['intro']['body'] ?? '').'</p>',
                    'background' => 'white',
                ],
            ],
            [
                'type' => 'link_cards',
                'data' => [
                    'eyebrow' => 'Serving',
                    'heading' => 'Start with a clear next step.',
                    'background' => 'black',
                    'cards' => collect($nextSteps)->map(fn (array $step): array => [
                        'title' => $step['title'] ?? '',
                        'summary' => $step['summary'] ?? null,
                        'url' => $step['url'] ?? '#',
                    ])->all(),
                ],
            ],
            [
                'type' => 'process_steps',
                'data' => [
                    'eyebrow' => $defaults['process']['eyebrow'] ?? null,
                    'heading' => $defaults['process']['title'] ?? null,
                    'background' => 'white',
                    'steps' => $defaults['process']['steps'] ?? [],
                ],
            ],
            [
                'type' => 'image_text',
                'data' => [
                    'eyebrow' => $feature['eyebrow'],
                    'heading' => $feature['title'],
                    'body' => '<p>'.$feature['body'].'</p>',
                    'button_label' => $feature['label'],
                    'button_url' => $feature['url'] === '#' ? null : $feature['url'],
                    'background' => 'forest',
                    'image_position' => 'right',
                ],
            ],
        ];
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

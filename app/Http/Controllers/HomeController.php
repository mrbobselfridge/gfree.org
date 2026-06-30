<?php

namespace App\Http\Controllers;

use App\Models\HomepageBanner;
use App\Models\HomepageContent;
use App\Models\NavigationLink;
use App\Models\SiteAlert;
use App\Models\SiteSetting;
use App\Support\ContentBlocks;
use App\Support\RichContent;
use App\Support\SiteVariables;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $settings = SiteSetting::query()->first();
        $homepageContent = HomepageContent::query()->first();
        $defaults = config('twyxtco.homepage');
        $now = now();

        $heroBanners = HomepageBanner::query()
            ->where('is_published', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->inRandomOrder()
            ->get();

        $navigationLinks = NavigationLink::topLevelHeaderLinks();
        $utilityLinks = NavigationLink::topLevelUtilityLinks();
        $siteAlerts = SiteAlert::query()
            ->active()
            ->publicOrder()
            ->get();

        $hero = $this->renderHeroSlide($this->hero($defaults['hero'], $heroBanners->first()), $settings);

        return view('home', [
            'settings' => $settings,
            'theme' => $defaults['theme'],
            'headerLinks' => $navigationLinks->isNotEmpty() ? $navigationLinks : collect($defaults['navigation']),
            'utilityLinks' => $utilityLinks,
            'utilitySocialLinks' => $settings?->socialLinks() ?? collect(),
            'siteAlerts' => $siteAlerts,
            'pageTitle' => $this->pageTitle($settings, $homepageContent),
            'pageDescription' => $this->pageDescription($settings, $homepageContent, $hero),
            'hero' => $hero,
            'heroSlides' => $this->heroSlides($defaults['hero'], $heroBanners, $settings),
            'heroBannersAutoRotate' => (bool) ($homepageContent?->hero_banners_auto_rotate ?? false),
            'heroBannersRotationDelayMilliseconds' => ($homepageContent?->heroBannersRotationDelaySeconds() ?? HomepageContent::DEFAULT_HERO_BANNERS_ROTATION_DELAY_SECONDS) * 1000,
            'heroBannersFadeDurationMilliseconds' => ($homepageContent?->heroBannersFadeDurationSeconds() ?? HomepageContent::DEFAULT_HERO_BANNERS_FADE_DURATION_SECONDS) * 1000,
            'contentBlocks' => $this->contentBlocks($homepageContent, $defaults, $settings, $now),
            'socialLinks' => $settings?->socialLinks() ?? collect(),
        ]);
    }

    private function pageTitle(?SiteSetting $settings, ?HomepageContent $content): string
    {
        return $content?->seo_title
            ?: $settings?->church_name
            ?: config('app.name', 'TwyxtCo Church');
    }

    private function pageDescription(?SiteSetting $settings, ?HomepageContent $content, array $hero): ?string
    {
        $description = $content?->seo_description
            ?: $settings?->tagline
            ?: ($hero['subtitle'] ?? null);

        return filled($description) ? RichContent::plainText($description) : null;
    }

    private function heroSlides(array $defaults, $banners, ?SiteSetting $settings)
    {
        if ($banners->isEmpty()) {
            return collect([$this->renderHeroSlide($defaults, $settings)]);
        }

        return $banners
            ->map(fn (HomepageBanner $banner): array => $this->renderHeroSlide($this->hero($defaults, $banner), $settings))
            ->values();
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

    private function renderHeroSlide(array $slide, ?SiteSetting $settings): array
    {
        return [
            ...$slide,
            'eyebrow_html' => SiteVariables::renderText($slide['eyebrow'] ?? '', $settings),
            'title_html' => SiteVariables::renderText($slide['title'] ?? '', $settings),
            'subtitle_html' => RichContent::renderTextarea($slide['subtitle'] ?? '', $settings),
            'primary_label_html' => SiteVariables::renderText($slide['primary_label'] ?? '', $settings),
            'secondary_label_html' => SiteVariables::renderText($slide['secondary_label'] ?? '', $settings),
        ];
    }

    private function feature(array $defaults, ?HomepageContent $content): array
    {
        $featureUrl = $content?->feature_url ?: $defaults['url'];

        return [
            'eyebrow' => $content?->feature_eyebrow ?: $defaults['eyebrow'],
            'title' => $content?->feature_title ?: $defaults['title'],
            'body' => $content?->feature_body ?: $defaults['body'],
            'label' => $content?->feature_label ?: $defaults['label'],
            'url' => $featureUrl,
        ];
    }

    private function contentBlocks(?HomepageContent $content, array $defaults, ?SiteSetting $settings, $now): array
    {
        $blocks = $content?->content_blocks;

        if (blank($blocks)) {
            $blocks = $this->defaultHomepageBlocks($defaults, $settings);
        }

        $blocks = collect($blocks)
            ->filter(fn (array $block): bool => $this->isHomepageBlockVisible($block, $now))
            ->filter(fn (array $block): bool => $block['type'] !== 'announcements_bar')
            ->values()
            ->all();

        return ContentBlocks::prepare($blocks, $settings);
    }

    private function isHomepageBlockVisible(array $block, $now): bool
    {
        $data = $block['data'] ?? [];
        $publishAt = $data['publish_at'] ?? null;
        $expiresAt = $data['expires_at'] ?? null;

        if (filled($publishAt) && $this->scheduleDate($publishAt)?->isAfter($now)) {
            return false;
        }

        if (filled($expiresAt) && $this->scheduleDate($expiresAt)?->isBefore($now)) {
            return false;
        }

        return true;
    }

    private function scheduleDate(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function defaultHomepageBlocks(array $defaults, ?SiteSetting $settings): array
    {
        $nextSteps = $defaults['next_steps'] ?? [];
        $feature = $this->feature($defaults['feature'], null);

        return [
            [
                    'type' => 'info_strip',
                    'data' => [
                        'spacing' => 'bottom',
                        'background_target' => 'item',
                        'items' => $this->defaultInfoStripItems($defaults['service_details'] ?? [], $settings),
                ],
            ],
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
                    'background_target' => 'page',
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
                    'background_target' => 'page',
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

    private function defaultInfoStripItems(array $serviceDetails, ?SiteSetting $settings): array
    {
        return collect($serviceDetails)
            ->map(function (array $detail, int $index) use ($settings): array {
                return [
                    'label' => $detail['label'] ?? null,
                    'value' => match ($index) {
                        0 => SiteVariables::variableValue('service-times', $settings) !== null ? '[[service-times]]' : ($detail['value'] ?? null),
                        1 => SiteVariables::variableValue('address', $settings) !== null ? '[[address]]' : ($detail['value'] ?? null),
                        default => $detail['value'] ?? null,
                    },
                ];
            })
            ->all();
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

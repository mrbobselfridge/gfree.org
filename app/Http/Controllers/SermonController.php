<?php

namespace App\Http\Controllers;

use App\Models\NavigationLink;
use App\Models\SiteSetting;
use App\Support\YoutubeSermonFeed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class SermonController extends Controller
{
    public function __invoke(YoutubeSermonFeed $feed): View
    {
        $settings = SiteSetting::query()->first();
        $defaults = config('gfree.homepage');
        $navigationLinks = NavigationLink::query()
            ->topLevelHeader()
            ->limit(10)
            ->get();

        return view('sermons.index', [
            'settings' => $settings,
            'headerLinks' => $navigationLinks->isNotEmpty() ? $navigationLinks : collect($defaults['navigation']),
            'socialLinks' => $this->socialLinks($settings),
            'hero' => $this->listingHero('sermons', [
                'small_label' => 'Messages',
                'title' => 'Sermons',
                'subtitle' => 'Watch recent messages from gFree Church.',
            ]),
            'introText' => $settings?->sermons_text,
            'sermons' => $feed->latest(feedUrl: $settings?->sermons_youtube_feed_url),
            'channelUrl' => $settings?->sermons_youtube_channel_url ?: 'https://www.youtube.com/@gfreesermons9521/videos',
            'channelLinkLabel' => $settings?->sermons_youtube_link_label ?: 'View on YouTube',
        ]);
    }

    private function listingHero(string $prefix, array $defaults): array
    {
        $settings = SiteSetting::query()->first();

        return [
            'small_label' => data_get($settings, "{$prefix}_small_label") ?: ($defaults['small_label'] ?? null),
            'title' => data_get($settings, "{$prefix}_title") ?: $defaults['title'],
            'subtitle' => data_get($settings, "{$prefix}_subtitle") ?: ($defaults['subtitle'] ?? null),
            'image_url' => $this->imageUrl(data_get($settings, "{$prefix}_image_path")),
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

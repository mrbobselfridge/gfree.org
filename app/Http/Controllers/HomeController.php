<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
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
        $defaults = config('gfree.homepage');
        $now = now();

        $heroBanner = HomepageBanner::query()
            ->where('is_published', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->orderBy('sort_order')
            ->latest()
            ->first();

        $navigationLinks = NavigationLink::query()
            ->where('is_published', true)
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
            ->where(fn ($query) => $query->whereNull('publish_at')->orWhere('publish_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', $now))
            ->orderByDesc('is_featured')
            ->orderByDesc('publish_at')
            ->latest()
            ->limit(3)
            ->get();

        return view('home', [
            'settings' => $settings,
            'theme' => $defaults['theme'],
            'headerLinks' => $navigationLinks->isNotEmpty() ? $navigationLinks : collect($defaults['navigation']),
            'hero' => $this->hero($defaults['hero'], $heroBanner),
            'serviceDetails' => $this->serviceDetails($defaults['service_details'], $settings),
            'intro' => $defaults['intro'],
            'nextSteps' => $ministries->isNotEmpty() ? $this->ministrySteps($ministries) : collect($defaults['next_steps']),
            'process' => $defaults['process'],
            'feature' => $this->feature($defaults['feature'], $settings),
            'updates' => $announcements->isNotEmpty() ? $this->announcementUpdates($announcements) : collect($defaults['updates']),
            'socialLinks' => $this->socialLinks($settings),
        ]);
    }

    private function hero(array $defaults, ?HomepageBanner $banner): array
    {
        if (! $banner) {
            return $defaults;
        }

        return [
            'eyebrow' => $defaults['eyebrow'],
            'title' => $banner->title,
            'subtitle' => $banner->subtitle ?: $defaults['subtitle'],
            'image_url' => $this->imageUrl($banner->image_path) ?: $defaults['image_url'],
            'primary_label' => $banner->button_label ?: $defaults['primary_label'],
            'primary_url' => $banner->button_url ?: $defaults['primary_url'],
            'secondary_label' => $banner->secondary_button_label ?: $defaults['secondary_label'],
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
            'url' => $ministry->one_church_url ?: url('/ministries/'.$ministry->slug),
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

    private function feature(array $defaults, ?SiteSetting $settings): array
    {
        if (! $settings?->one_church_url) {
            return $defaults;
        }

        $defaults['url'] = $settings->one_church_url;

        return $defaults;
    }

    private function socialLinks(?SiteSetting $settings)
    {
        return collect([
            ['label' => 'Facebook', 'url' => $settings?->facebook_url],
            ['label' => 'Instagram', 'url' => $settings?->instagram_url],
            ['label' => 'YouTube', 'url' => $settings?->youtube_url],
        ])->filter(fn (array $link) => filled($link['url']));
    }

    private function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\NavigationLink;
use App\Models\SiteSetting;
use App\Support\ContentBlocks;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = $this->activeAnnouncements()
            ->paginate(12)
            ->through(function (Announcement $announcement): Announcement {
                $announcement->image_url = $this->imageUrl($announcement->image_path);

                return $announcement;
            });

        return view('announcements.index', [
            ...$this->sharedViewData(),
            'announcements' => $announcements,
            'hero' => $this->listingHero('announcements', [
                'title' => 'Announcements',
                'subtitle' => 'Current updates, next steps, and opportunities around gFree Church.',
            ]),
        ]);
    }

    public function show(string $slug): View
    {
        $announcement = $this->activeAnnouncements()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('announcements.show', [
            ...$this->sharedViewData(),
            'announcement' => $announcement,
            'contentBlocks' => ContentBlocks::prepare($announcement->content_blocks, SiteSetting::query()->first()),
            'imageUrl' => $this->imageUrl($announcement->image_path) ?: $this->listingImageUrl('announcements'),
        ]);
    }

    private function activeAnnouncements()
    {
        $now = now();

        return Announcement::query()
            ->where('is_published', true)
            ->where(fn ($query) => $query->whereNull('publish_at')->orWhere('publish_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', $now))
            ->orderByDesc('is_featured')
            ->orderByDesc('publish_at')
            ->latest();
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

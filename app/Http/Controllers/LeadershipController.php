<?php

namespace App\Http\Controllers;

use App\Models\NavigationLink;
use App\Models\SiteSetting;
use App\Models\StaffMember;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class LeadershipController extends Controller
{
    public function index(): View
    {
        $leaders = StaffMember::query()
            ->where('is_published', true)
            ->whereNotNull('slug')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (StaffMember $leader): StaffMember {
                $leader->photo_url = $this->imageUrl($leader->photo_path);

                return $leader;
            });

        return view('leadership.index', [
            ...$this->sharedViewData(),
            'leaders' => $leaders,
            'hero' => $this->listingHero('leadership', [
                'title' => 'Meet the people serving gFree.',
                'subtitle' => 'Staff and lay leaders helping our church follow Jesus together.',
            ]),
        ]);
    }

    public function show(string $slug): View
    {
        $leader = StaffMember::query()
            ->where('is_published', true)
            ->where('slug', $slug)
            ->firstOrFail();

        return view('leadership.show', [
            ...$this->sharedViewData(),
            'leader' => $leader,
            'photoUrl' => $this->imageUrl($leader->photo_path) ?: $this->listingImageUrl('leadership'),
        ]);
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

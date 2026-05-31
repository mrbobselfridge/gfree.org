<?php

namespace App\Http\Controllers;

use App\Models\Bulletin;
use App\Models\NavigationLink;
use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class BulletinController extends Controller
{
    public function index(): View
    {
        $bulletins = $this->publishedBulletins()
            ->paginate(12)
            ->through(function (Bulletin $bulletin): Bulletin {
                $bulletin->public_url = route('bulletins.show', $bulletin->bulletin_date->toDateString());
                $bulletin->pdf_url = $this->pdfUrl($bulletin->pdf_path);

                return $bulletin;
            });

        return view('bulletins.index', [
            ...$this->sharedViewData(),
            'bulletins' => $bulletins,
            'hero' => $this->listingHero('bulletins', [
                'small_label' => 'Bulletins',
                'title' => 'Bulletins',
                'subtitle' => 'View recent weekly bulletins from gFree Church.',
            ]),
        ]);
    }

    public function show(string $date): View
    {
        $bulletin = $this->publishedBulletins()
            ->whereDate('bulletin_date', $date)
            ->firstOrFail();

        return view('bulletins.show', [
            ...$this->sharedViewData(),
            'bulletin' => $bulletin,
            'pdfUrl' => $this->pdfUrl($bulletin->pdf_path),
        ]);
    }

    private function publishedBulletins(): Builder
    {
        return Bulletin::query()
            ->where('is_published', true)
            ->whereNotNull('bulletin_date')
            ->orderByDesc('bulletin_date')
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

    private function socialLinks(?SiteSetting $settings)
    {
        return collect([
            ['label' => 'Facebook', 'url' => $settings?->facebook_url],
            ['label' => 'Instagram', 'url' => $settings?->instagram_url],
            ['label' => 'YouTube', 'url' => $settings?->youtube_url],
        ])->filter(fn (array $link) => filled($link['url']));
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

    private function pdfUrl(?string $path): ?string
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

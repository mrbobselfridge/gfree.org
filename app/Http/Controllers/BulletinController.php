<?php

namespace App\Http\Controllers;

use App\Models\Bulletin;
use App\Models\NavigationLink;
use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BulletinController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $bulletins = $this->publishedBulletins()
            ->when($search !== '', fn (Builder $query) => $this->searchBulletins($query, $search))
            ->paginate(12)
            ->withQueryString()
            ->through(function (Bulletin $bulletin): Bulletin {
                $bulletin->public_url = route('bulletins.show', $bulletin->bulletin_date->toDateString());
                $bulletin->pdf_url = $this->pdfUrl($bulletin->pdf_path);

                return $bulletin;
            });

        return view('bulletins.index', [
            ...$this->sharedViewData(),
            'bulletins' => $bulletins,
            'search' => $search,
            'hero' => $this->listingHero('bulletins', [
                'small_label' => 'Bulletins',
                'title' => 'Bulletins',
                'subtitle' => 'View recent weekly bulletins from TwyxtCo Church.',
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
            'hero' => $this->listingHero('bulletins', [
                'small_label' => 'Bulletin',
                'title' => $bulletin->title,
                'subtitle' => $bulletin->bulletin_date->format('F j, Y'),
            ]),
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

    private function searchBulletins(Builder $query, string $search): Builder
    {
        $like = "%{$search}%";

        return $query->where(function (Builder $query) use ($like): void {
            $query
                ->where('title', 'like', $like)
                ->orWhere('extracted_html', 'like', $like)
                ->orWhere('bulletin_date', 'like', $like);
        });
    }

    private function sharedViewData(): array
    {
        $settings = SiteSetting::query()->first();
        $defaults = config('twyxtco.homepage');

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

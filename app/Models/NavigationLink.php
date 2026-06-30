<?php

namespace App\Models;

use App\Contracts\HasPublicUrl;
use App\Support\PublicPageUrls;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[Fillable([
    'parent_id',
    'label',
    'url',
    'location',
    'sort_order',
    'publish_at',
    'expires_at',
    'opens_in_new_tab',
    'is_published',
])]
class NavigationLink extends Model implements HasPublicUrl
{
    public const LOCATION_HEADER = 'header';

    public const LOCATION_UTILITY = 'utility';

    private bool $matchingPageResolved = false;

    private ?Page $matchingPageCache = null;

    public function publicUrl(): ?string
    {
        return PublicPageUrls::normalize($this->url);
    }

    protected function casts(): array
    {
        return [
            'publish_at' => 'datetime',
            'expires_at' => 'datetime',
            'opens_in_new_tab' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (NavigationLink $link): void {
            if ($link->location === self::LOCATION_UTILITY) {
                $link->parent_id = null;
            }
        });
    }

    public static function locationOptions(): array
    {
        return [
            self::LOCATION_HEADER => 'Header',
            self::LOCATION_UTILITY => 'Utility',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_published', true)
            ->where(fn (Builder $query) => $query->whereNull('publish_at')->orWhere('publish_at', '<=', $now))
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', $now));
    }

    public function scopeTopLevelHeader(Builder $query): Builder
    {
        return $query
            ->active()
            ->where('location', self::LOCATION_HEADER)
            ->whereNull('parent_id')
            ->with(['children' => fn (HasMany $query) => $query
                ->active()
                ->where('location', self::LOCATION_HEADER)
                ->orderBy('sort_order')
                ->orderBy('label'),
            ])
            ->orderBy('sort_order')
            ->orderBy('label');
    }

    public static function topLevelHeaderLinks(int $limit = 10): Collection
    {
        $links = self::query()
            ->topLevelHeader()
            ->get();

        self::loadMatchingPages(
            $links->flatMap(fn (NavigationLink $link): Collection => collect([$link])->merge($link->children))
        );

        return $links
            ->filter(fn (NavigationLink $link): bool => $link->targetPageAllowsNavigation())
            ->map(function (NavigationLink $link): NavigationLink {
                $link->setRelation(
                    'children',
                    $link->children
                        ->filter(fn (NavigationLink $child): bool => $child->targetPageAllowsNavigation())
                        ->values()
                );

                return $link;
            })
            ->take($limit)
            ->values();
    }

    public function scopeTopLevelUtility(Builder $query): Builder
    {
        return $query
            ->active()
            ->where('location', self::LOCATION_UTILITY)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('label');
    }

    public static function topLevelUtilityLinks(): Collection
    {
        $links = self::query()
            ->topLevelUtility()
            ->get();

        self::loadMatchingPages($links);

        return $links
            ->filter(fn (NavigationLink $link): bool => $link->targetPageAllowsNavigation())
            ->values();
    }

    public function matchingPageSlug(): ?string
    {
        $url = trim((string) $this->url);

        if (blank($url) || $url === '#') {
            return null;
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);
            $urlHost = parse_url($url, PHP_URL_HOST);

            if (! $appHost || ! $urlHost || strcasecmp($appHost, $urlHost) !== 0) {
                return null;
            }
        } elseif (! Str::startsWith($url, '/')) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $slug = trim((string) $path, '/');

        return filled($slug) ? $slug : null;
    }

    public function matchingPage(): ?Page
    {
        if ($this->matchingPageResolved) {
            return $this->matchingPageCache;
        }

        $this->matchingPageResolved = true;
        $slug = $this->matchingPageSlug();

        if ($slug === null) {
            return $this->matchingPageCache = null;
        }

        return $this->matchingPageCache = Page::query()
            ->where('slug', $slug)
            ->first();
    }

    public function targetPageAllowsNavigation(): bool
    {
        $page = $this->matchingPage();

        return $page === null || $page->isActive();
    }

    public function pageLimitLabel(): string
    {
        $slug = $this->matchingPageSlug();

        if ($slug === null) {
            return 'No page match';
        }

        $page = $this->matchingPage();

        if ($page === null) {
            return 'No page match';
        }

        if (! $page->is_published) {
            return 'Hidden: page draft';
        }

        if ($page->publish_at?->isFuture()) {
            return 'Hidden: page future';
        }

        if ($page->expires_at?->isPast()) {
            return 'Hidden: page expired';
        }

        if ($page->publish_at !== null || $page->expires_at !== null) {
            return 'Page window active';
        }

        return 'Page live';
    }

    public function pageLimitDescription(): ?string
    {
        $slug = $this->matchingPageSlug();

        if ($slug === null) {
            return 'External links, anchors, home, and system routes are limited only by Navigation dates.';
        }

        $page = $this->matchingPage();

        if ($page === null) {
            return "No Page record uses /{$slug}, so Page dates do not affect this link.";
        }

        $publishAt = $page->publish_at?->format('M j, Y g:i A') ?? 'not set';
        $expiresAt = $page->expires_at?->format('M j, Y g:i A') ?? 'not set';

        return "Matches /{$slug}. Page Publish at: {$publishAt}. Page Expires at: {$expiresAt}.";
    }

    public function pageLimitColor(): string
    {
        return match ($this->pageLimitLabel()) {
            'Hidden: page draft', 'Hidden: page expired' => 'danger',
            'Hidden: page future' => 'warning',
            'Page window active' => 'info',
            'Page live' => 'success',
            default => 'gray',
        };
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    private static function loadMatchingPages(Collection $links): void
    {
        $links = $links->filter(fn (NavigationLink $link): bool => $link->matchingPageSlug() !== null);
        $slugs = $links
            ->map(fn (NavigationLink $link): ?string => $link->matchingPageSlug())
            ->filter()
            ->unique()
            ->values();

        if ($slugs->isEmpty()) {
            return;
        }

        $pages = Page::query()
            ->whereIn('slug', $slugs)
            ->get()
            ->keyBy('slug');

        $links->each(fn (NavigationLink $link): ?Page => $link->setMatchingPage(
            $pages->get($link->matchingPageSlug())
        ));
    }

    private function setMatchingPage(?Page $page): ?Page
    {
        $this->matchingPageResolved = true;
        $this->matchingPageCache = $page;

        return $page;
    }
}

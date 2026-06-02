<?php

namespace App\Http\Middleware;

use App\Models\AnalyticsPageView;
use App\Models\Announcement;
use App\Models\Bulletin;
use App\Models\Ministry;
use App\Models\Page;
use App\Models\StaffMember;
use App\Support\UserAgentDetails;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;
use Symfony\Component\HttpFoundation\Response;

class TrackPublicPageView
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            $this->record($request, $response);
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || ! $response->isSuccessful()) {
            return false;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return false;
        }

        if ($request->is([
            'admin*',
            'livewire*',
            'storage*',
            'build*',
            'css*',
            'js*',
            'fonts*',
            'images*',
            'favicon.ico',
            'robots.txt',
            'up',
        ])) {
            return false;
        }

        $contentType = (string) $response->headers->get('content-type');

        if (filled($contentType) && ! str_contains($contentType, 'text/html')) {
            return false;
        }

        return Schema::hasTable('analytics_page_views');
    }

    private function record(Request $request, Response $response): void
    {
        $userAgent = $request->userAgent();
        $details = UserAgentDetails::parse($userAgent);
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;
        $ipHash = $this->hash($request->ip());
        $sessionHash = $this->hash($sessionId);
        $location = $this->locationFields($request);

        AnalyticsPageView::query()->create([
            'url' => $request->fullUrl(),
            'path' => '/'.ltrim($request->path(), '/'),
            'route_name' => $request->route()?->getName(),
            'page_title' => $this->pageTitle($response),
            'referrer_url' => $request->headers->get('referer'),
            'referrer_domain' => $this->referrerDomain($request),
            'user_agent' => $userAgent,
            'browser' => $details['browser'],
            'platform' => $details['platform'],
            'device_type' => $details['device_type'],
            'ip_hash' => $ipHash,
            'visitor_hash' => $this->hash(implode('|', array_filter([$request->ip(), $userAgent]))),
            'session_hash' => $sessionHash,
            'viewed_at' => now(),
        ] + $location);
    }

    private function locationFields(Request $request): array
    {
        $position = rescue(
            fn (): Position|bool => Location::get($request->ip()),
            false,
            false,
        );

        if (! $position instanceof Position || $position->isEmpty()) {
            return [];
        }

        return [
            'country_code' => $this->limit($position->countryCode, 2),
            'country_name' => $this->limit($position->countryName),
            'region_code' => $this->limit($position->regionCode),
            'region_name' => $this->limit($position->regionName),
            'city_name' => $this->limit($position->cityName),
            'postal_code' => $this->limit($position->postalCode ?? $position->zipCode),
            'timezone' => $this->limit($position->timezone),
            'latitude' => $this->decimal($position->latitude),
            'longitude' => $this->decimal($position->longitude),
            'location_driver' => $this->limit($position->driver),
        ];
    }

    private function pageTitle(Response $response): ?string
    {
        if (! method_exists($response, 'getOriginalContent')) {
            return null;
        }

        $original = $response->getOriginalContent();

        if (! $original instanceof View) {
            return null;
        }

        $data = $original->getData();

        foreach ([
            'page' => fn (Page $page): string => $page->title,
            'announcement' => fn (Announcement $announcement): string => $announcement->title,
            'ministry' => fn (Ministry $ministry): string => $ministry->name,
            'leader' => fn (StaffMember $leader): string => $leader->name,
            'bulletin' => fn (Bulletin $bulletin): string => $bulletin->title,
        ] as $key => $resolver) {
            $value = $data[$key] ?? null;

            if ($value) {
                return Str::limit($resolver($value), 255, '');
            }
        }

        $heroTitle = data_get($data, 'hero.title');

        if (filled($heroTitle)) {
            return Str::limit((string) $heroTitle, 255, '');
        }

        if (filled($data['concept']['name'] ?? null)) {
            return Str::limit((string) $data['concept']['name'], 255, '');
        }

        if (($data['settings'] ?? null) && filled($data['settings']->church_name)) {
            return Str::limit((string) $data['settings']->church_name, 255, '');
        }

        return null;
    }

    private function referrerDomain(Request $request): ?string
    {
        $referrer = $request->headers->get('referer');

        if (blank($referrer)) {
            return null;
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        if (! is_string($host) || blank($host)) {
            return null;
        }

        $host = str($host)->lower()->remove('www.')->toString();
        $currentHost = str((string) $request->getHost())->lower()->remove('www.')->toString();

        return $host === $currentHost ? null : $host;
    }

    private function hash(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    private function limit(?string $value, int $limit = 255): ?string
    {
        return filled($value) ? Str::limit($value, $limit, '') : null;
    }

    private function decimal(?string $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}

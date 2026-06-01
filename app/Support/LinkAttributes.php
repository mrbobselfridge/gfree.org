<?php

namespace App\Support;

class LinkAttributes
{
    public static function externalAttributes(?string $url): string
    {
        return self::isExternal($url) ? ' target="_blank" rel="noopener noreferrer"' : '';
    }

    public static function isExternal(?string $url): bool
    {
        if (blank($url)) {
            return false;
        }

        $url = trim($url);

        if (str_starts_with($url, '//')) {
            return true;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return false;
        }

        $localHosts = collect([
            request()?->getHost(),
            parse_url(config('app.url'), PHP_URL_HOST),
        ])
            ->filter()
            ->map(fn (string $host): string => strtolower($host))
            ->unique()
            ->all();

        return ! in_array(strtolower($host), $localHosts, true);
    }
}

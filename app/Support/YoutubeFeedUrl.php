<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class YoutubeFeedUrl
{
    public static function fromChannelUrl(?string $channelUrl): ?string
    {
        $channelId = self::channelIdFromUrl($channelUrl);

        if (! $channelId && filled($channelUrl)) {
            $channelId = self::channelIdFromPage((string) $channelUrl);
        }

        return $channelId ? "https://www.youtube.com/feeds/videos.xml?channel_id={$channelId}" : null;
    }

    private static function channelIdFromUrl(?string $channelUrl): ?string
    {
        if (blank($channelUrl)) {
            return null;
        }

        $path = parse_url((string) $channelUrl, PHP_URL_PATH);

        if (! is_string($path)) {
            return null;
        }

        return preg_match('#/channel/([A-Za-z0-9_-]+)#', $path, $matches)
            ? $matches[1]
            : null;
    }

    private static function channelIdFromPage(string $channelUrl): ?string
    {
        if (! in_array(parse_url($channelUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return null;
        }

        $response = Http::timeout(8)->get($channelUrl);

        if (! $response->successful()) {
            return null;
        }

        return self::channelIdFromHtml($response->body());
    }

    private static function channelIdFromHtml(string $html): ?string
    {
        foreach ([
            '/<meta[^>]+itemprop=["\']channelId["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/["\']channelId["\']\s*:\s*["\']([^"\']+)["\']/i',
            '/["\']browseId["\']\s*:\s*["\'](UC[A-Za-z0-9_-]+)["\']/i',
        ] as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}

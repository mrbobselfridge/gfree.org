<?php

namespace App\Support;

use Illuminate\Support\Str;

class UserAgentDetails
{
    /**
     * @return array{browser: string, platform: string, device_type: string}
     */
    public static function parse(?string $userAgent): array
    {
        $userAgent = (string) $userAgent;
        $lower = Str::lower($userAgent);

        return [
            'browser' => self::browser($lower),
            'platform' => self::platform($lower),
            'device_type' => self::deviceType($lower),
        ];
    }

    private static function browser(string $userAgent): string
    {
        return match (true) {
            Str::contains($userAgent, ['bot', 'crawl', 'spider', 'slurp']) => 'Bot',
            Str::contains($userAgent, 'edg/') => 'Microsoft Edge',
            Str::contains($userAgent, 'opr/') || Str::contains($userAgent, 'opera') => 'Opera',
            Str::contains($userAgent, 'samsungbrowser') => 'Samsung Internet',
            Str::contains($userAgent, 'firefox/') => 'Firefox',
            Str::contains($userAgent, 'chrome/') || Str::contains($userAgent, 'chromium/') => 'Chrome',
            Str::contains($userAgent, 'safari/') => 'Safari',
            blank($userAgent) => 'Unknown',
            default => 'Other',
        };
    }

    private static function platform(string $userAgent): string
    {
        return match (true) {
            Str::contains($userAgent, ['bot', 'crawl', 'spider', 'slurp']) => 'Bot',
            Str::contains($userAgent, 'android') => 'Android',
            Str::contains($userAgent, ['iphone', 'ipad', 'ipod']) => 'iOS',
            Str::contains($userAgent, 'windows') => 'Windows',
            Str::contains($userAgent, ['macintosh', 'mac os x']) => 'macOS',
            Str::contains($userAgent, 'linux') => 'Linux',
            blank($userAgent) => 'Unknown',
            default => 'Other',
        };
    }

    private static function deviceType(string $userAgent): string
    {
        return match (true) {
            Str::contains($userAgent, ['bot', 'crawl', 'spider', 'slurp']) => 'Bot',
            Str::contains($userAgent, ['ipad', 'tablet']) => 'Tablet',
            Str::contains($userAgent, ['mobile', 'iphone', 'android']) => 'Mobile',
            blank($userAgent) => 'Unknown',
            default => 'Desktop',
        };
    }
}

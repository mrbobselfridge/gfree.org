<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class SiteDesignPalette
{
    /**
     * @return array<int, array{key: string, name: string, hex: string}>
     */
    public static function defaultBackgroundColors(): array
    {
        return [
            ['key' => 'white', 'name' => 'White', 'hex' => '#ffffff'],
            ['key' => 'black', 'name' => 'Black', 'hex' => '#050505'],
            ['key' => 'teal', 'name' => 'Teal', 'hex' => '#17b8ad'],
            ['key' => 'gold', 'name' => 'Gold', 'hex' => '#f5c84b'],
            ['key' => 'forest', 'name' => 'Forest', 'hex' => '#163f36'],
            ['key' => 'clay', 'name' => 'Clay', 'hex' => '#c96f5a'],
        ];
    }

    /**
     * @return array<int, array{key: string, name: string, hex: string}>
     */
    public static function backgroundColors(): array
    {
        try {
            if (Schema::hasTable('site_settings') && Schema::hasColumn('site_settings', 'design_background_colors')) {
                $colors = self::normalizeBackgroundColors(SiteSetting::query()->value('design_background_colors'));

                if ($colors !== []) {
                    return $colors;
                }
            }
        } catch (Throwable) {
            //
        }

        return self::defaultBackgroundColors();
    }

    /**
     * @return array<string, string>
     */
    public static function backgroundOptions(): array
    {
        return collect(self::backgroundColors())
            ->mapWithKeys(fn (array $color): array => [$color['key'] => $color['name']])
            ->all();
    }

    /**
     * @return array<int, array{key: string, name: string, hex: string}>
     */
    public static function normalizeBackgroundColors(mixed $colors): array
    {
        if (is_string($colors)) {
            $decoded = json_decode($colors, true);
            $colors = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($colors)) {
            return [];
        }

        $seen = [];

        return collect($colors)
            ->filter(fn (mixed $color): bool => is_array($color))
            ->map(function (array $color) use (&$seen): ?array {
                $name = Str::of((string) ($color['name'] ?? ''))->trim()->limit(80, '')->toString();
                $hex = self::normalizeHex($color['hex'] ?? null);

                if ($name === '' || $hex === null) {
                    return null;
                }

                $baseKey = self::normalizeKey($color['key'] ?? $name) ?? 'background';
                $key = $baseKey;
                $index = 2;

                while (isset($seen[$key])) {
                    $key = $baseKey.'-'.$index;
                    $index++;
                }

                $seen[$key] = true;

                return [
                    'key' => $key,
                    'name' => $name,
                    'hex' => $hex,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public static function normalizeHex(mixed $hex): ?string
    {
        $hex = strtolower(trim((string) $hex));

        if ($hex === '') {
            return null;
        }

        if (! str_starts_with($hex, '#')) {
            $hex = '#'.$hex;
        }

        if (! preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/', $hex)) {
            return null;
        }

        return $hex;
    }

    public static function normalizeKey(mixed $key): ?string
    {
        $key = Str::slug((string) $key);

        return $key === '' ? null : $key;
    }

    public static function backgroundKey(mixed $key): string
    {
        return match ($key) {
            'dark' => 'black',
            'light' => 'white',
            default => self::normalizeKey($key) ?? 'white',
        };
    }

    public static function pageBlockStyle(mixed $key): ?string
    {
        return self::styleFor($key, 'page-block');
    }

    public static function relatedContentStyle(mixed $key): ?string
    {
        return self::styleFor($key, 'related-content');
    }

    private static function styleFor(mixed $key, string $context): ?string
    {
        $key = self::backgroundKey($key);
        $color = self::colorByKey($key);

        if ($color === null || self::matchesDefaultColor($color)) {
            return null;
        }

        return $context === 'related-content'
            ? self::relatedContentVariables($color['hex'])
            : self::pageBlockVariables($color['hex']);
    }

    /**
     * @return array{key: string, name: string, hex: string}|null
     */
    private static function colorByKey(string $key): ?array
    {
        return collect(self::backgroundColors())->firstWhere('key', $key)
            ?? collect(self::defaultBackgroundColors())->firstWhere('key', 'white');
    }

    /**
     * @param  array{key: string, name: string, hex: string}  $color
     */
    private static function matchesDefaultColor(array $color): bool
    {
        $default = collect(self::defaultBackgroundColors())->firstWhere('key', $color['key']);

        return $default !== null && $default['hex'] === $color['hex'];
    }

    private static function pageBlockVariables(string $hex): string
    {
        $dark = self::isDark($hex);
        $fg = $dark ? '#ffffff' : '#141414';
        $muted = $dark ? 'rgba(255, 255, 255, 0.72)' : 'rgba(20, 20, 20, 0.76)';
        $accent = $dark ? '#17b8ad' : '#063f3c';
        $buttonBg = $dark ? '#17b8ad' : '#141414';
        $buttonFg = $dark ? '#141414' : '#ffffff';
        $cardBg = $dark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(255, 255, 255, 0.48)';
        $border = $dark ? 'rgba(255, 255, 255, 0.14)' : 'rgba(20, 20, 20, 0.12)';

        return self::variables([
            '--page-block-bg' => $hex,
            '--page-block-fg' => $fg,
            '--page-block-muted' => $muted,
            '--page-block-eyebrow' => $accent,
            '--page-block-link' => $accent,
            '--page-block-button-bg' => $buttonBg,
            '--page-block-button-fg' => $buttonFg,
            '--page-block-card-bg' => $cardBg,
            '--page-block-card-fg' => $fg,
            '--page-block-card-muted' => $muted,
            '--page-block-border' => $border,
        ]);
    }

    private static function relatedContentVariables(string $hex): string
    {
        $dark = self::isDark($hex);
        $fg = $dark ? '#ffffff' : '#141414';
        $link = $dark ? '#17b8ad' : '#078780';
        $cardBg = $dark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(255, 255, 255, 0.9)';
        $cardFg = $dark ? '#ffffff' : '#141414';
        $cardMuted = $dark ? 'rgba(255, 255, 255, 0.72)' : 'rgba(20, 20, 20, 0.72)';

        return self::variables([
            'background' => $hex,
            'color' => $fg,
            '--concept-updates-link' => $link,
            '--concept-updates-card-bg' => $cardBg,
            '--concept-updates-card-fg' => $cardFg,
            '--concept-updates-card-muted' => $cardMuted,
        ]);
    }

    /**
     * @param  array<string, string>  $variables
     */
    private static function variables(array $variables): string
    {
        return collect($variables)
            ->map(fn (string $value, string $property): string => $property.': '.$value)
            ->implode('; ');
    }

    private static function isDark(string $hex): bool
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = collect(str_split($hex))->map(fn (string $value): string => $value.$value)->implode('');
        }

        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));

        return (($red * 299) + ($green * 587) + ($blue * 114)) / 1000 < 145;
    }
}

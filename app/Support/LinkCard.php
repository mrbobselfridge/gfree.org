<?php

namespace App\Support;

use Illuminate\Support\Str;

class LinkCard
{
    public const TYPE_DISPLAY = 'display';

    public const TYPE_LINK_SAME = 'link_same';

    public const TYPE_LINK_NEW = 'link_new';

    public const TYPE_FLIP_IMAGE = 'flip_image';

    public const TYPE_FLIP_HTML = 'flip_html';

    public const TYPE_JAVASCRIPT_WIDGET = 'javascript_widget';

    public static function typeOptions(bool $includeCodeTypes = false): array
    {
        $options = [
            self::TYPE_DISPLAY => 'Nothing / display only',
            self::TYPE_LINK_SAME => 'Link opens in same tab',
            self::TYPE_LINK_NEW => 'Link opens in new tab',
            self::TYPE_FLIP_IMAGE => 'Flip card with image back',
        ];

        if ($includeCodeTypes) {
            $options[self::TYPE_FLIP_HTML] = 'Flip card with HTML back';
            $options[self::TYPE_JAVASCRIPT_WIDGET] = 'JavaScript widget';
        }

        return $options;
    }

    public static function normalizeType(?string $type, ?string $url = null): string
    {
        if (in_array($type, array_keys(self::typeOptions(true)), true)) {
            return $type;
        }

        return filled($url) ? self::TYPE_LINK_SAME : self::TYPE_DISPLAY;
    }

    public static function isCodeType(?string $type): bool
    {
        return in_array($type, [self::TYPE_FLIP_HTML, self::TYPE_JAVASCRIPT_WIDGET], true);
    }

    public static function imageFitOptions(): array
    {
        return [
            'cover' => 'Fill card (crop/zoom)',
            'contain' => 'Fit full image',
        ];
    }

    public static function normalizeImageFit(?string $fit): string
    {
        return array_key_exists((string) $fit, self::imageFitOptions()) ? (string) $fit : 'cover';
    }

    public static function imageFocusOptions(): array
    {
        return [
            'center' => 'Center',
            'top' => 'Top',
            'bottom' => 'Bottom',
            'left' => 'Left',
            'right' => 'Right',
        ];
    }

    public static function imageFocusPosition(?string $focus, mixed $x = null, mixed $y = null): string
    {
        if (is_numeric($x) || is_numeric($y)) {
            return self::normalizeImageFocusPercent($x).' '.self::normalizeImageFocusPercent($y);
        }

        return self::legacyImageFocusPosition($focus);
    }

    public static function legacyImageFocusPosition(?string $focus): string
    {
        return match ($focus) {
            'top' => 'center top',
            'bottom' => 'center bottom',
            'left' => 'left center',
            'right' => 'right center',
            default => 'center center',
        };
    }

    public static function normalizeImageFocusPercent(mixed $value): string
    {
        if (! is_numeric($value)) {
            return '50%';
        }

        return max(0, min(100, (int) $value)).'%';
    }

    public static function legacyImageFocusPercent(?string $focus, string $axis): int
    {
        return match ($axis) {
            'x' => match ($focus) {
                'left' => 0,
                'right' => 100,
                default => 50,
            },
            'y' => match ($focus) {
                'top' => 0,
                'bottom' => 100,
                default => 50,
            },
            default => 50,
        };
    }

    public static function normalizeImageZoom(mixed $zoom): int
    {
        if (! is_numeric($zoom)) {
            return 100;
        }

        return max(100, min(200, (int) $zoom));
    }

    public static function isSafeHref(?string $url): bool
    {
        $url = trim((string) $url);

        if ($url === '') {
            return false;
        }

        if (preg_match('/<[^>]+>/', $url) === 1) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return $scheme === null || in_array(strtolower($scheme), ['http', 'https', 'mailto', 'tel'], true);
    }

    public static function newKey(): string
    {
        return Str::lower(Str::random(10));
    }

    public static function sanitizedKey(?string $key): string
    {
        $key = Str::of((string) $key)
            ->lower()
            ->replaceMatches('/[^a-z0-9_-]+/', '-')
            ->trim('-')
            ->toString();

        return $key !== '' ? $key : self::newKey();
    }

    public static function flipId(?string $key): string
    {
        return 'content-card-flip-'.self::sanitizedKey($key);
    }

    public static function widgetId(?string $key): string
    {
        return 'content-card-widget-'.self::sanitizedKey($key);
    }
}

<?php

namespace App\Support;

class PageSlugs
{
    public const RESERVED_PREFIXES = [
        'admin',
        'api',
        'build',
        'concept-screens',
        'concepts',
        'css',
        'files',
        'fonts',
        'images',
        'js',
        'livewire',
        'manual',
        'storage',
        'up',
    ];

    public static function routePattern(): string
    {
        $reserved = collect(self::RESERVED_PREFIXES)
            ->map(fn (string $prefix): string => preg_quote($prefix, '#'))
            ->implode('|');

        return "^(?!(?i:(?:{$reserved}))(?:/|$))[A-Za-z0-9()\-]+(?:/[A-Za-z0-9()\-]+)*$";
    }

    public static function isValidPath(string $slug): bool
    {
        return preg_match('#'.self::routePattern().'#', $slug) === 1;
    }

    public static function reservedPrefix(string $slug): ?string
    {
        $firstSegment = strtolower(strtok($slug, '/') ?: $slug);

        return in_array($firstSegment, self::RESERVED_PREFIXES, true)
            ? $firstSegment
            : null;
    }
}

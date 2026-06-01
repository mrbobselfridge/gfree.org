<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class MediaLibrary
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function images(): Collection
    {
        $disk = Storage::disk('public');

        return collect($disk->allFiles())
            ->filter(fn (string $path): bool => self::isImage($path))
            ->map(function (string $path) use ($disk): array {
                $absolutePath = method_exists($disk, 'path') ? $disk->path($path) : null;
                $dimensions = self::dimensions($absolutePath);

                return [
                    'path' => $path,
                    'name' => basename($path),
                    'directory' => dirname($path) === '.' ? '' : dirname($path),
                    'url' => $disk->url($path),
                    'size' => $disk->size($path),
                    'size_for_humans' => Number::fileSize($disk->size($path)),
                    'modified' => $disk->lastModified($path),
                    'dimensions' => $dimensions,
                    'dimensions_for_humans' => $dimensions ? "{$dimensions[0]} x {$dimensions[1]}" : null,
                ];
            })
            ->sortByDesc('modified')
            ->values();
    }

    public static function imageOptions(): array
    {
        return self::images()
            ->groupBy(fn (array $image): string => $image['directory'] ?: 'Uploaded images')
            ->map(fn (Collection $images): array => $images
                ->mapWithKeys(fn (array $image): array => [
                    $image['path'] => self::optionLabel($image),
                ])
                ->all())
            ->all();
    }

    public static function imageOptionLabel(string $path): ?string
    {
        $image = self::images()->firstWhere('path', $path);

        return $image ? self::optionLabel($image) : null;
    }

    private static function optionLabel(array $image): string
    {
        $name = e($image['name']);
        $path = e($image['path']);
        $url = e($image['url']);
        $meta = collect([$image['dimensions_for_humans'] ?? null, $image['size_for_humans'] ?? null])
            ->filter()
            ->implode(' | ');

        return <<<HTML
            <span class="flex items-center gap-3">
                <img src="{$url}" alt="" class="h-10 w-14 shrink-0 rounded object-cover ring-1 ring-gray-950/10 dark:ring-white/20">
                <span class="min-w-0">
                    <span class="block truncate font-medium">{$name}</span>
                    <span class="block truncate text-xs text-gray-500">{$path}</span>
                    <span class="block truncate text-xs text-gray-400">{$meta}</span>
                </span>
            </span>
        HTML;
    }

    private static function isImage(string $path): bool
    {
        return Str::of($path)
            ->lower()
            ->endsWith(['.jpg', '.jpeg', '.png', '.gif', '.webp', '.avif', '.svg']);
    }

    private static function dimensions(?string $path): ?array
    {
        if (! $path || ! is_file($path) || Str::endsWith(Str::lower($path), '.svg')) {
            return null;
        }

        $dimensions = @getimagesize($path);

        return $dimensions ? [$dimensions[0], $dimensions[1]] : null;
    }
}

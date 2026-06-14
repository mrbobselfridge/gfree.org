<?php

namespace App\Support;

use App\Models\MediaImageMetadata;
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
        $metadata = MediaImageMetadata::query()
            ->get()
            ->keyBy('path');

        $images = collect($disk->allFiles())
            ->filter(fn (string $path): bool => self::isImage($path))
            ->map(function (string $path) use ($disk, $metadata): array {
                $absolutePath = method_exists($disk, 'path') ? $disk->path($path) : null;
                $dimensions = self::dimensions($absolutePath);
                /** @var MediaImageMetadata|null $imageMetadata */
                $imageMetadata = $metadata->get($path);

                return [
                    'path' => $path,
                    'name' => basename($path),
                    'title' => $imageMetadata?->title,
                    'display_title' => $imageMetadata?->title ?: basename($path),
                    'slug' => $imageMetadata?->slug,
                    'tags' => $imageMetadata?->tags ?? [],
                    'directory' => dirname($path) === '.' ? '' : dirname($path),
                    'url' => $disk->url($path),
                    'download_url' => route('admin.media-images.download', ['path' => $path]),
                    'size' => $disk->size($path),
                    'size_for_humans' => Number::fileSize($disk->size($path)),
                    'modified' => $disk->lastModified($path),
                    'dimensions' => $dimensions,
                    'dimensions_for_humans' => $dimensions ? "{$dimensions[0]} x {$dimensions[1]}" : null,
                ];
            })
            ->sortByDesc('modified')
            ->values();

        $usage = MediaUsage::forImages($images->pluck('path')->all());

        return $images
            ->map(function (array $image) use ($usage): array {
                $image['usage'] = $usage[$image['path']] ?? [];
                $image['usage_count'] = count($image['usage']);
                $image['usage_summary'] = $image['usage_count'] > 0
                    ? self::usageSummary($image['usage'])
                    : 'Unused';

                return $image;
            });
    }

    /**
     * @return array<string, string>
     */
    public static function tagOptions(): array
    {
        $existingPaths = self::images()->pluck('path')->all();

        if ($existingPaths === []) {
            return [];
        }

        return MediaImageMetadata::query()
            ->whereIn('path', $existingPaths)
            ->pluck('tags')
            ->flatMap(fn (?array $tags): array => MediaImageMetadata::normalizeTags($tags ?? []))
            ->unique(fn (string $tag): string => Str::of($tag)->lower()->toString())
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->mapWithKeys(fn (string $tag): array => [$tag => $tag])
            ->all();
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
        $name = e($image['display_title'] ?: $image['name']);
        $path = e($image['path']);
        $url = e($image['url']);
        $tags = collect($image['tags'] ?? [])->implode(', ');
        $meta = collect([$image['dimensions_for_humans'] ?? null, $image['size_for_humans'] ?? null, $tags ?: null])
            ->filter()
            ->implode(' | ');

        return <<<HTML
            <span class="flex items-center gap-3">
                <img src="{$url}" alt="" class="h-10 w-14 shrink-0 rounded object-cover ring-1 ring-gray-950/10 dark:ring-white/20">
                <span class="min-w-0">
                    <span class="block truncate font-medium">{$name}</span>
                    <span class="block truncate text-xs text-gray-500">{$path}</span>
                    <span class="block truncate text-xs text-gray-400">{$meta}</span>
                    <span class="block truncate text-xs text-gray-500">{$image['usage_summary']}</span>
                </span>
            </span>
        HTML;
    }

    /**
     * @param  array<int, array<string, string>>  $usage
     */
    private static function usageSummary(array $usage): string
    {
        $first = collect($usage)->first();
        $summary = ($first['short_label'] ?? $first['label'] ?? 'Used').' - '.($first['detail'] ?? 'Image');
        $remaining = count($usage) - 1;

        return $remaining > 0 ? "{$summary} + {$remaining} more" : $summary;
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

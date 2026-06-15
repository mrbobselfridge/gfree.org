<?php

namespace App\Support;

use App\Models\MediaImageMetadata;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class MediaLibrary
{
    private const IMAGE_INDEX_CACHE_KEY = 'media-library.image-index.v1';

    private const IMAGE_INDEX_CACHE_SECONDS = 60;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function images(): Collection
    {
        if (app()->environment('testing')) {
            return self::buildImageIndex();
        }

        return Cache::remember(
            self::IMAGE_INDEX_CACHE_KEY,
            now()->addSeconds(self::IMAGE_INDEX_CACHE_SECONDS),
            fn (): Collection => self::buildImageIndex(),
        );
    }

    /**
     * @return array{
     *     items: Collection<int, array<string, mixed>>,
     *     total: int,
     *     filtered_total: int,
     *     has_more: bool,
     * }
     */
    public static function pagedImages(?string $search = null, string $sort = 'recent', int $limit = 24, int $offset = 0): array
    {
        $images = self::images();
        $total = $images->count();

        if (filled($search)) {
            $needle = str($search)->lower()->toString();
            $images = $images->filter(fn (array $image): bool => str(self::searchHaystack($image))
                ->lower()
                ->contains($needle));
        }

        $images = self::sortImages($images, $sort)->values();
        $filteredTotal = $images->count();
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        return [
            'items' => $images->slice($offset, $limit)->values(),
            'total' => $total,
            'filtered_total' => $filteredTotal,
            'has_more' => ($offset + $limit) < $filteredTotal,
        ];
    }

    public static function image(string $path): ?array
    {
        return self::images()->firstWhere('path', $path);
    }

    public static function clearImageIndexCache(): void
    {
        Cache::forget(self::IMAGE_INDEX_CACHE_KEY);
    }

    /**
     * @return array<string, string>
     */
    public static function sortOptions(): array
    {
        return [
            'recent' => 'Most recent',
            'content_type' => 'Content Type',
            'file_name' => 'File Name',
            'size' => 'Size',
            'path' => 'File Path + Name',
            'dimensions' => 'Dimensions',
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function buildImageIndex(): Collection
    {
        $disk = Storage::disk('public');
        $metadata = MediaImageMetadata::query()
            ->with('createdBy')
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
                    'created_at' => $imageMetadata?->created_at,
                    'updated_at' => $imageMetadata?->updated_at,
                    'created_at_for_humans' => self::formatMetadataDate($imageMetadata?->created_at),
                    'updated_at_for_humans' => self::formatMetadataDate($imageMetadata?->updated_at),
                    'created_by_user_id' => $imageMetadata?->created_by_user_id,
                    'created_by_name' => $imageMetadata?->createdBy?->name,
                    'created_by_email' => $imageMetadata?->createdBy?->email,
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

    private static function formatMetadataDate(mixed $date): ?string
    {
        if (! $date) {
            return null;
        }

        return $date->format('M j, Y g:i A');
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

    private static function searchHaystack(array $image): string
    {
        return collect([
            $image['name'] ?? null,
            $image['title'] ?? null,
            $image['slug'] ?? null,
            ...($image['tags'] ?? []),
            $image['created_at_for_humans'] ?? null,
            $image['updated_at_for_humans'] ?? null,
            $image['created_by_name'] ?? null,
            $image['created_by_email'] ?? null,
            $image['path'] ?? null,
            $image['directory'] ?? null,
            $image['usage_summary'] ?? null,
            ...collect($image['usage'] ?? [])
                ->flatMap(fn (array $usage): array => [
                    $usage['label'] ?? null,
                    $usage['short_label'] ?? null,
                    $usage['detail'] ?? null,
                ])
                ->all(),
        ])
            ->filter()
            ->implode(' ');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $images
     * @return Collection<int, array<string, mixed>>
     */
    private static function sortImages(Collection $images, string $sort): Collection
    {
        return match ($sort) {
            'content_type' => $images->sortBy(fn (array $image): string => self::contentTypeSortValue($image), SORT_NATURAL | SORT_FLAG_CASE),
            'file_name' => $images->sortBy(fn (array $image): string => (string) ($image['name'] ?? ''), SORT_NATURAL | SORT_FLAG_CASE),
            'size' => $images->sortByDesc(fn (array $image): int => (int) ($image['size'] ?? 0)),
            'path' => $images->sortBy(fn (array $image): string => (string) ($image['path'] ?? ''), SORT_NATURAL | SORT_FLAG_CASE),
            'dimensions' => $images->sortByDesc(fn (array $image): int => self::dimensionSortValue($image)),
            default => $images->sortByDesc(fn (array $image): int => (int) ($image['modified'] ?? 0)),
        };
    }

    private static function contentTypeSortValue(array $image): string
    {
        $usage = collect($image['usage'] ?? [])->first();

        if (! $usage) {
            return 'zz-unused';
        }

        return (string) ($usage['short_label'] ?? $usage['label'] ?? '');
    }

    private static function dimensionSortValue(array $image): int
    {
        $dimensions = $image['dimensions'] ?? null;

        if (! is_array($dimensions) || count($dimensions) < 2) {
            return 0;
        }

        return (int) $dimensions[0] * (int) $dimensions[1];
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

<?php

namespace App\Support;

use App\Models\FileDocument;
use App\Models\MediaImageMetadata;
use Illuminate\Support\Str;

class MediaTagOptions
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect([
            ...self::imageTags(),
            ...self::fileTags(),
        ])
            ->unique(fn (string $tag): string => Str::lower($tag))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->mapWithKeys(fn (string $tag): array => [$tag => $tag])
            ->all();
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<string, string>
     */
    public static function optionsWithSelected(array $values): array
    {
        return collect(self::options())
            ->merge(self::labelsFor($values))
            ->sortKeysUsing('strcasecmp')
            ->all();
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<string, string>
     */
    public static function labelsFor(array $values): array
    {
        $options = self::options();

        return collect(MediaImageMetadata::normalizeTags($values))
            ->mapWithKeys(fn (string $tag): array => [$tag => $options[$tag] ?? $tag])
            ->all();
    }

    public static function normalizeCreatedTag(mixed $value): string
    {
        return MediaImageMetadata::normalizeTags([$value])[0] ?? '';
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    public static function validationValues(array $values): array
    {
        return collect($values)
            ->flatten()
            ->map(fn (mixed $tag): string => trim((string) $tag))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function imageTags(): array
    {
        $existingPaths = MediaLibrary::images()->pluck('path')->all();

        if ($existingPaths === []) {
            return [];
        }

        return MediaImageMetadata::query()
            ->whereIn('path', $existingPaths)
            ->pluck('tags')
            ->flatMap(fn (?array $tags): array => MediaImageMetadata::normalizeTags($tags ?? []))
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function fileTags(): array
    {
        return FileDocument::query()
            ->pluck('tags')
            ->flatMap(fn (?array $tags): array => FileDocument::normalizeTags($tags ?? []))
            ->all();
    }
}

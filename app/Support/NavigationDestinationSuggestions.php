<?php

namespace App\Support;

use App\Models\FileDocument;
use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NavigationDestinationSuggestions
{
    public static function optionValues(?string $search = null, int $perTypeLimit = 100): array
    {
        $search = self::normalizeSearch($search);
        $perTypeLimit = max(1, $perTypeLimit);

        return collect()
            ->merge(self::pageDestinations($search, $perTypeLimit))
            ->merge(self::fileDestinations($search, $perTypeLimit))
            ->merge(self::mediaDestinations($search, $perTypeLimit))
            ->pluck('url')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private static function pageDestinations(?string $search, int $limit): Collection
    {
        return Page::query()
            ->active()
            ->when($search, fn (Builder $query): Builder => $query
                ->where(fn (Builder $query): Builder => $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")))
            ->orderBy('title')
            ->limit($limit)
            ->get(['title', 'slug'])
            ->map(fn (Page $page): array => [
                'url' => self::localPath($page->publicUrl()),
            ]);
    }

    private static function fileDestinations(?string $search, int $limit): Collection
    {
        return RichTextFileLibrary::publicDocumentQuery()
            ->when($search, fn (Builder $query): Builder => $query
                ->where(fn (Builder $query): Builder => $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")))
            ->orderBy('title')
            ->limit($limit)
            ->get(['id', 'title', 'file_name', 'category', 'visibility', 'is_published', 'publish_at', 'expires_at', 'current_version_id'])
            ->map(fn (FileDocument $document): array => [
                'url' => self::localPath($document->publicUrl()),
            ]);
    }

    private static function mediaDestinations(?string $search, int $limit): Collection
    {
        $images = filled($search)
            ? MediaLibrary::pagedImages($search, limit: $limit)['items']
            : MediaLibrary::images()->take($limit);

        return collect($images)
            ->map(fn (array $image): array => [
                'url' => self::localPath($image['url'] ?? null),
            ]);
    }

    private static function normalizeSearch(?string $search): ?string
    {
        $search = trim((string) $search);
        $search = ltrim($search, '/');

        return $search === '' ? null : $search;
    }

    private static function localPath(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (blank($path)) {
            return null;
        }

        return Str::startsWith($path, '/') ? $path : "/{$path}";
    }
}

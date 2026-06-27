<?php

namespace App\Support;

use App\Models\MediaImageMetadata;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UnsplashImageImporter
{
    public function __construct(private readonly UnsplashClient $client) {}

    /**
     * @return array{path: string, metadata: MediaImageMetadata}
     */
    public function import(string $photoId, string $directory, ?int $createdByUserId = null): array
    {
        $photo = $this->client->photo($photoId);
        $downloadUrl = $this->client->imageDownloadUrl($photo);

        if (blank($photo['id'] ?? null) || blank($downloadUrl)) {
            throw new \RuntimeException('The selected Unsplash photo could not be loaded.');
        }

        $download = $this->client->downloadImage((string) $downloadUrl);
        $path = $this->pathForPhoto($photo, $directory, $download['content_type']);

        Storage::disk('public')->put($path, $download['contents']);

        $downloadLocation = $photo['links']['download_location'] ?? null;

        if (filled($downloadLocation)) {
            $this->client->trackDownload((string) $downloadLocation);
        }

        $metadata = MediaImageMetadata::query()->firstOrNew(['path' => $path]);

        if (! $metadata->exists) {
            $metadata->created_by_user_id = $createdByUserId;
        }

        $title = $this->titleForPhoto($photo);
        $metadata->fill([
            'title' => $title,
            'slug' => $this->uniqueSlug(MediaImageMetadata::normalizeSlug($title), $path),
            'tags' => MediaImageMetadata::mergeAutoTags(['unsplash'], $title),
            'source' => 'unsplash',
            'source_id' => $photo['id'],
            'source_url' => $photo['links']['html'] ?? null,
            'source_author_name' => $photo['author_name'] ?? null,
            'source_author_url' => $photo['author_url'] ?? null,
        ]);
        $metadata->save();

        MediaLibrary::clearImageIndexCache();

        return [
            'path' => $path,
            'metadata' => $metadata,
        ];
    }

    /**
     * @param  array<string, mixed>  $photo
     */
    private function titleForPhoto(array $photo): string
    {
        $description = trim((string) ($photo['description'] ?: $photo['alt_description'] ?: ''));

        if ($description !== '') {
            return Str::limit(Str::headline($description), 120, '');
        }

        $author = trim((string) ($photo['author_name'] ?? ''));

        return $author !== '' ? "Unsplash Photo by {$author}" : 'Unsplash Photo';
    }

    /**
     * @param  array<string, mixed>  $photo
     */
    private function pathForPhoto(array $photo, string $directory, ?string $contentType): string
    {
        $directory = trim($directory, '/') ?: 'media-library';
        $title = Str::slug($this->titleForPhoto($photo)) ?: 'unsplash-photo';
        $extension = $this->extensionForContentType($contentType);

        return str("{$directory}/".Str::ulid()."/{$title}.{$extension}")
            ->lower()
            ->toString();
    }

    private function extensionForContentType(?string $contentType): string
    {
        return match (strtolower(trim((string) str($contentType)->before(';')))) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }

    private function uniqueSlug(?string $slug, string $ignorePath): ?string
    {
        if (blank($slug)) {
            return null;
        }

        $base = $slug;
        $candidate = $base;
        $counter = 2;

        while (MediaImageMetadata::query()
            ->where('slug', $candidate)
            ->where('path', '!=', $ignorePath)
            ->exists()) {
            $candidate = "{$base}-{$counter}";
            $counter++;
        }

        return $candidate;
    }
}

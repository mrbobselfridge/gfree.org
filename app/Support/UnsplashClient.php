<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class UnsplashClient
{
    public function configured(): bool
    {
        return filled($this->accessKey());
    }

    /**
     * @return array{total: int, total_pages: int, results: array<int, array<string, mixed>>}
     */
    public function searchPhotos(string $query, int $page = 1, int $perPage = 12): array
    {
        if (! $this->configured() || blank($query)) {
            return [
                'total' => 0,
                'total_pages' => 0,
                'results' => [],
            ];
        }

        $response = $this->request()
            ->get('/search/photos', [
                'query' => $query,
                'page' => max(1, $page),
                'per_page' => max(1, min(30, $perPage)),
                'content_filter' => 'high',
            ])
            ->throw();

        $payload = $response->json();

        return [
            'total' => (int) ($payload['total'] ?? 0),
            'total_pages' => (int) ($payload['total_pages'] ?? 0),
            'results' => collect($payload['results'] ?? [])
                ->map(fn (array $photo): array => $this->normalizePhoto($photo))
                ->filter(fn (array $photo): bool => filled($photo['id'] ?? null))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function photo(string $id): array
    {
        if (! $this->configured() || blank($id)) {
            return [];
        }

        $response = $this->request()
            ->get('/photos/'.rawurlencode($id))
            ->throw();

        return $this->normalizePhoto($response->json() ?? []);
    }

    /**
     * @return array{contents: string, content_type: ?string}
     */
    public function downloadImage(string $url): array
    {
        $response = Http::timeout(20)
            ->accept('*/*')
            ->get($url)
            ->throw();

        return [
            'contents' => $response->body(),
            'content_type' => $response->header('Content-Type'),
        ];
    }

    public function trackDownload(string $downloadLocation): void
    {
        if (! $this->configured() || blank($downloadLocation)) {
            return;
        }

        $this->request()
            ->get($downloadLocation)
            ->throw();
    }

    public function imageDownloadUrl(array $photo, int $width = 2400): ?string
    {
        $rawUrl = $photo['urls']['raw'] ?? null;

        if (blank($rawUrl)) {
            return $photo['urls']['full'] ?? $photo['urls']['regular'] ?? null;
        }

        $separator = str_contains($rawUrl, '?') ? '&' : '?';

        return $rawUrl.$separator.http_build_query([
            'w' => $width,
            'q' => 85,
            'fm' => 'jpg',
            'fit' => 'max',
        ]);
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.unsplash.api_url', 'https://api.unsplash.com'), '/'))
            ->timeout(10)
            ->acceptJson()
            ->withHeaders([
                'Authorization' => 'Client-ID '.$this->accessKey(),
                'Accept-Version' => 'v1',
            ]);
    }

    private function accessKey(): ?string
    {
        return config('services.unsplash.access_key');
    }

    /**
     * @param  array<string, mixed>  $photo
     * @return array<string, mixed>
     */
    private function normalizePhoto(array $photo): array
    {
        $user = is_array($photo['user'] ?? null) ? $photo['user'] : [];

        return [
            'id' => (string) ($photo['id'] ?? ''),
            'description' => $photo['description'] ?? null,
            'alt_description' => $photo['alt_description'] ?? null,
            'width' => $photo['width'] ?? null,
            'height' => $photo['height'] ?? null,
            'format' => $this->formatForDimensions($photo['width'] ?? null, $photo['height'] ?? null),
            'color' => $photo['color'] ?? null,
            'urls' => is_array($photo['urls'] ?? null) ? $photo['urls'] : [],
            'links' => is_array($photo['links'] ?? null) ? $photo['links'] : [],
            'author_name' => $user['name'] ?? null,
            'author_username' => $user['username'] ?? null,
            'author_url' => $user['links']['html'] ?? null,
            'thumb_url' => $photo['urls']['thumb'] ?? $photo['urls']['small'] ?? null,
            'preview_url' => $photo['urls']['regular'] ?? $photo['urls']['small'] ?? null,
        ];
    }

    private function formatForDimensions(mixed $width, mixed $height): ?string
    {
        $width = (int) $width;
        $height = (int) $height;

        if ($width <= 0 || $height <= 0) {
            return null;
        }

        $ratio = $width / $height;

        return match (true) {
            $ratio >= 1.75 => 'Banner',
            $ratio > 1.08 => 'Horizontal',
            $ratio >= 0.92 => 'Square',
            default => 'Vertical',
        };
    }
}

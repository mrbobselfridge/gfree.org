<?php

namespace App\Filament\Admin\Forms\Components;

use App\Support\UnsplashClient;
use Filament\Forms\Components\Field;

class UnsplashImagePicker extends Field
{
    public const DEFAULT_PER_PAGE = 25;

    protected string $view = 'filament.admin.forms.components.unsplash-image-picker';

    protected string $searchField = 'unsplash_search';

    public function searchField(string $field): static
    {
        $this->searchField = $field;

        return $this;
    }

    /**
     * @return array{
     *     configured: bool,
     *     query: ?string,
     *     total: int,
     *     total_pages: int,
     *     results: array<int, array<string, mixed>>,
     *     error: ?string,
     * }
     */
    public function getSearchResults(): array
    {
        /** @var UnsplashClient $client */
        $client = app(UnsplashClient::class);
        $query = trim((string) $this->makeGetUtility()($this->searchField));

        if (! $client->configured()) {
            return $this->emptyResults($query, configured: false);
        }

        if ($query === '') {
            return $this->emptyResults(null, configured: true);
        }

        try {
            return [
                'configured' => true,
                'query' => $query,
                ...$client->searchPhotos($query, perPage: self::DEFAULT_PER_PAGE),
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            return [
                ...$this->emptyResults($query, configured: true),
                'error' => 'Unsplash search failed. Check the API key and try again.',
            ];
        }
    }

    /**
     * @return array{
     *     configured: bool,
     *     query: ?string,
     *     total: int,
     *     total_pages: int,
     *     results: array<int, array<string, mixed>>,
     *     error: ?string,
     * }
     */
    private function emptyResults(?string $query, bool $configured): array
    {
        return [
            'configured' => $configured,
            'query' => $query,
            'total' => 0,
            'total_pages' => 0,
            'results' => [],
            'error' => null,
        ];
    }
}

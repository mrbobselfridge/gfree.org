<?php

namespace App\Filament\Admin\Forms\Components;

use App\Support\UnsplashClient;
use Filament\Forms\Components\Field;

class UnsplashImagePicker extends Field
{
    public const DEFAULT_PER_PAGE = 25;

    protected string $view = 'filament.admin.forms.components.unsplash-image-picker';

    protected string $searchField = 'unsplash_search';

    protected string $pageField = 'unsplash_page';

    public function searchField(string $field): static
    {
        $this->searchField = $field;

        return $this;
    }

    public function pageField(string $field): static
    {
        $this->pageField = $field;

        return $this;
    }

    /**
     * @return array{
     *     configured: bool,
     *     query: ?string,
     *     total: int,
     *     total_pages: int,
     *     page: int,
     *     per_page: int,
     *     results: array<int, array<string, mixed>>,
     *     has_more: bool,
     *     error: ?string,
     * }
     */
    public function getSearchResults(): array
    {
        /** @var UnsplashClient $client */
        $client = app(UnsplashClient::class);
        $get = $this->makeGetUtility();
        $query = trim((string) $get($this->searchField));
        $page = max(1, (int) ($get($this->pageField) ?: 1));

        if (! $client->configured()) {
            return $this->emptyResults($query, configured: false);
        }

        if ($query === '') {
            return $this->emptyResults(null, configured: true);
        }

        try {
            $results = [];
            $total = 0;
            $totalPages = 0;

            for ($currentPage = 1; $currentPage <= $page; $currentPage++) {
                $response = $client->searchPhotos($query, page: $currentPage, perPage: self::DEFAULT_PER_PAGE);

                $total = (int) ($response['total'] ?? $total);
                $totalPages = (int) ($response['total_pages'] ?? $totalPages);
                $results = [
                    ...$results,
                    ...($response['results'] ?? []),
                ];

                if ($totalPages > 0 && $currentPage >= $totalPages) {
                    break;
                }
            }

            $results = collect($results)
                ->unique('id')
                ->values()
                ->all();

            return [
                'configured' => true,
                'query' => $query,
                'total' => $total,
                'total_pages' => $totalPages,
                'page' => $page,
                'per_page' => self::DEFAULT_PER_PAGE,
                'results' => $results,
                'has_more' => count($results) < $total && ($totalPages === 0 || $page < $totalPages),
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
     *     page: int,
     *     per_page: int,
     *     results: array<int, array<string, mixed>>,
     *     has_more: bool,
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
            'page' => 1,
            'per_page' => self::DEFAULT_PER_PAGE,
            'results' => [],
            'has_more' => false,
            'error' => null,
        ];
    }

    public function getPageStatePath(): string
    {
        return $this->resolveRelativeStatePath($this->pageField);
    }

    public function getNextPage(): int
    {
        return max(1, (int) ($this->makeGetUtility()($this->pageField) ?: 1)) + 1;
    }
}

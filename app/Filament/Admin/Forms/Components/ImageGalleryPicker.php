<?php

namespace App\Filament\Admin\Forms\Components;

use App\Support\MediaLibrary;
use Filament\Forms\Components\Field;
use Illuminate\Support\Collection;

class ImageGalleryPicker extends Field
{
    public const DEFAULT_LIMIT = 24;

    public const BATCH_SIZE = 24;

    protected string $view = 'filament.admin.forms.components.image-gallery-picker';

    protected string $searchField = 'existing_image_search';

    protected string $sortField = 'existing_image_sort';

    protected string $limitField = 'existing_image_limit';

    public function searchField(string $field): static
    {
        $this->searchField = $field;

        return $this;
    }

    public function sortField(string $field): static
    {
        $this->sortField = $field;

        return $this;
    }

    public function limitField(string $field): static
    {
        $this->limitField = $field;

        return $this;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getImages(): Collection
    {
        return $this->getImageResults()['items'];
    }

    /**
     * @return array{
     *     items: Collection<int, array<string, mixed>>,
     *     total: int,
     *     filtered_total: int,
     *     has_more: bool,
     * }
     */
    public function getImageResults(): array
    {
        $get = $this->makeGetUtility();
        $search = $get($this->searchField);
        $sort = $get($this->sortField) ?: 'recent';
        $limit = (int) ($get($this->limitField) ?: self::DEFAULT_LIMIT);
        $results = MediaLibrary::pagedImages(
            search: filled($search) ? (string) $search : null,
            sort: (string) $sort,
            limit: $limit,
        );
        $selectedPath = $this->firstImagePath($this->getState());

        if (blank($search) && filled($selectedPath) && ! $results['items']->contains('path', $selectedPath)) {
            $selectedImage = MediaLibrary::image($selectedPath);

            if ($selectedImage) {
                $results['items'] = collect([$selectedImage])
                    ->merge($results['items'])
                    ->unique('path')
                    ->values();
            }
        }

        return $results;
    }

    public function getLimitStatePath(): string
    {
        return $this->resolveRelativeStatePath($this->limitField);
    }

    public function getCurrentLimit(): int
    {
        $limit = $this->makeGetUtility()($this->limitField);

        return max(1, (int) ($limit ?: self::DEFAULT_LIMIT));
    }

    public function getNextLimit(): int
    {
        return $this->getCurrentLimit() + self::BATCH_SIZE;
    }

    private function firstImagePath(mixed $state): ?string
    {
        if (is_array($state)) {
            $state = collect($state)->first();
        }

        return filled($state) ? (string) $state : null;
    }
}

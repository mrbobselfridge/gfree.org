<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Pages\Concerns\RequiresAdminPageAccess;
use App\Support\MediaLibrary as MediaLibrarySupport;
use App\Support\MediaUsage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MediaLibrary extends Page
{
    use RequiresAdminPageAccess;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Media Library';

    protected static ?string $title = 'Media Library';

    protected static ?string $slug = 'media-library';

    protected string $view = 'filament.admin.pages.media-library';

    public string $search = '';

    public string $sort = 'recent';

    public function updatedSearch(): void
    {
        $this->search = trim($this->search);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getImages(): Collection
    {
        $images = MediaLibrarySupport::images();

        if (filled($this->search)) {
            $search = str($this->search)->lower()->toString();

            $images = $images->filter(fn (array $image): bool => str($this->searchHaystack($image))
                ->lower()
                ->contains($search));
        }

        return $this->sortImages($images)->values();
    }

    public function getTotalImageCount(): int
    {
        return MediaLibrarySupport::images()->count();
    }

    public function getSortOptions(): array
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadImages')
                ->label('Upload new')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('success')
                ->schema([
                    FileUpload::make('images')
                        ->label('Images')
                        ->image()
                        ->multiple()
                        ->disk('public')
                        ->directory('media-library')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $count = count($data['images'] ?? []);

                    Notification::make()
                        ->title($count === 1 ? 'Image uploaded' : "{$count} images uploaded")
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function replaceImageAction(): Action
    {
        return Action::make('replaceImage')
            ->label('Replace image')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->modalHeading('Replace image')
            ->modalDescription('Upload a replacement image. Every tracked place using the selected image will be updated to the new image.')
            ->modalSubmitActionLabel('Replace image')
            ->schema([
                FileUpload::make('replacement_image')
                    ->label('Replacement image')
                    ->image()
                    ->disk('public')
                    ->directory('media-library/replacements')
                    ->required(),
            ])
            ->action(function (array $arguments, array $data): void {
                $oldPath = (string) ($arguments['path'] ?? '');
                $replacement = $data['replacement_image'] ?? null;
                $newPath = is_array($replacement) ? collect($replacement)->first() : $replacement;

                if (blank($oldPath) || blank($newPath)) {
                    return;
                }

                $updated = MediaUsage::replaceImagePath($oldPath, (string) $newPath);
                Storage::disk('public')->delete($oldPath);

                Notification::make()
                    ->title('Image replaced')
                    ->body("Updated {$updated} tracked ".str('location')->plural($updated).'.')
                    ->success()
                    ->send();
            });
    }

    protected function deleteImageAction(): Action
    {
        return Action::make('deleteImage')
            ->label('Delete image')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete image')
            ->modalDescription(fn (array $arguments): string => $this->deleteImageDescription((string) ($arguments['path'] ?? '')))
            ->modalSubmitActionLabel('Delete image')
            ->action(function (array $arguments): void {
                $path = (string) ($arguments['path'] ?? '');

                if (blank($path)) {
                    return;
                }

                Storage::disk('public')->delete($path);

                Notification::make()
                    ->title('Image deleted')
                    ->success()
                    ->send();
            });
    }

    private function deleteImageDescription(string $path): string
    {
        $usage = MediaUsage::forImages([$path])[$path] ?? [];

        if ($usage === []) {
            return 'This image is not currently used in any tracked image field or content block.';
        }

        $usedIn = collect($usage)
            ->map(fn (array $item): string => "{$item['label']} ({$item['detail']})")
            ->implode(', ');

        return "Warning: this image is currently used in {$usedIn}. Deleting it will leave those places without this file.";
    }

    private function searchHaystack(array $image): string
    {
        return collect([
            $image['name'] ?? null,
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
    private function sortImages(Collection $images): Collection
    {
        return match ($this->sort) {
            'content_type' => $images->sortBy(fn (array $image): string => $this->contentTypeSortValue($image), SORT_NATURAL | SORT_FLAG_CASE),
            'file_name' => $images->sortBy(fn (array $image): string => (string) ($image['name'] ?? ''), SORT_NATURAL | SORT_FLAG_CASE),
            'size' => $images->sortByDesc(fn (array $image): int => (int) ($image['size'] ?? 0)),
            'path' => $images->sortBy(fn (array $image): string => (string) ($image['path'] ?? ''), SORT_NATURAL | SORT_FLAG_CASE),
            'dimensions' => $images->sortByDesc(fn (array $image): int => $this->dimensionSortValue($image)),
            default => $images->sortByDesc(fn (array $image): int => (int) ($image['modified'] ?? 0)),
        };
    }

    private function contentTypeSortValue(array $image): string
    {
        $usage = collect($image['usage'] ?? [])->first();

        if (! $usage) {
            return 'zz-unused';
        }

        return (string) ($usage['short_label'] ?? $usage['label'] ?? '');
    }

    private function dimensionSortValue(array $image): int
    {
        $dimensions = $image['dimensions'] ?? null;

        if (! is_array($dimensions) || count($dimensions) < 2) {
            return 0;
        }

        return (int) $dimensions[0] * (int) $dimensions[1];
    }
}

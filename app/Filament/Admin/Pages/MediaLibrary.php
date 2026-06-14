<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Pages\Concerns\RequiresAdminPageAccess;
use App\Filament\Admin\Support\IconOnlyAction;
use App\Models\MediaImageMetadata;
use App\Models\WorkflowNotificationRule;
use App\Support\AdminAccess;
use App\Support\MediaLibrary as MediaLibrarySupport;
use App\Support\MediaUsage;
use App\Support\WorkflowNotificationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MediaLibrary extends Page
{
    use RequiresAdminPageAccess;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 900;

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

    public function canAccessImages(): bool
    {
        return AdminAccess::canAccessToolOrAssignedRecords(Filament::auth()->user(), AdminAccess::MEDIA_LIBRARY);
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

    public function replaceImageClickHandler(string $path): ?string
    {
        return $this->replaceImageAction()(['path' => $path])->getLivewireClickHandler();
    }

    public function editImageMetadataClickHandler(string $path): ?string
    {
        return $this->editImageMetadataAction()(['path' => $path])->getLivewireClickHandler();
    }

    public function deleteImageClickHandler(string $path): ?string
    {
        return $this->deleteImageAction()(['path' => $path])->getLivewireClickHandler();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function uploadImagesAction(): Action
    {
        return Action::make('uploadImages')
            ->label('Upload new')
            ->color('success')
            ->modalHeading('Upload images')
            ->modalSubmitActionLabel('Upload')
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

                collect($data['images'] ?? [])
                    ->map(fn (mixed $path): string => (string) $path)
                    ->each(fn (string $path): mixed => app(WorkflowNotificationService::class)->automatic(
                        area: AdminAccess::MEDIA_LIBRARY,
                        trigger: WorkflowNotificationRule::TRIGGER_CREATED,
                        recordKey: 'media-library:'.$path,
                        recordLabel: basename($path),
                        adminUrl: static::getUrl(),
                    ));

                Notification::make()
                    ->title($count === 1 ? 'Image uploaded' : "{$count} images uploaded")
                    ->success()
                    ->send();
            });
    }

    protected function replaceImageAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('replaceImage')
                ->label('Replace image')
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
                    MediaImageMetadata::query()
                        ->where('path', $oldPath)
                        ->update(['path' => (string) $newPath]);
                    Storage::disk('public')->delete($oldPath);

                    app(WorkflowNotificationService::class)->automatic(
                        area: AdminAccess::MEDIA_LIBRARY,
                        trigger: WorkflowNotificationRule::TRIGGER_UPDATED,
                        recordKey: 'media-library:'.$oldPath,
                        recordLabel: basename($oldPath),
                        adminUrl: static::getUrl(),
                    );

                    Notification::make()
                        ->title('Image replaced')
                        ->body("Updated {$updated} tracked ".str('location')->plural($updated).'.')
                        ->success()
                        ->send();
                }),
            Heroicon::OutlinedPencilSquare,
        );
    }

    protected function editImageMetadataAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('editImageMetadata')
                ->label('Edit details')
                ->modalHeading('Edit image details')
                ->modalSubmitActionLabel('Save details')
                ->fillForm(fn (array $arguments): array => $this->imageMetadataFormData((string) ($arguments['path'] ?? '')))
                ->schema([
                    TextInput::make('title')
                        ->label('Title')
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label('Optional Slug / Path')
                        ->helperText('Optional. Use lowercase words separated by dashes. Slashes are allowed for grouped paths.')
                        ->maxLength(255)
                        ->dehydrateStateUsing(fn (?string $state): ?string => MediaImageMetadata::normalizeSlug($state)),
                    TagsInput::make('tags')
                        ->label('Tags')
                        ->placeholder('Add tag')
                        ->suggestions(fn (): array => array_values(MediaLibrarySupport::tagOptions()))
                        ->splitKeys(['Tab', ','])
                        ->reorderable()
                        ->nestedRecursiveRules(['max:80']),
                ])
                ->action(function (array $arguments, array $data): void {
                    $path = (string) ($arguments['path'] ?? '');

                    if (blank($path)) {
                        return;
                    }

                    $slug = MediaImageMetadata::normalizeSlug($data['slug'] ?? null);

                    validator(
                        ['slug' => $slug],
                        [
                            'slug' => [
                                'nullable',
                                'max:255',
                                Rule::unique('media_image_metadata', 'slug')->ignore($path, 'path'),
                            ],
                        ],
                    )->validate();

                    MediaImageMetadata::query()->updateOrCreate(
                        ['path' => $path],
                        [
                            'title' => filled($data['title'] ?? null) ? trim((string) $data['title']) : null,
                            'slug' => $slug,
                            'tags' => MediaImageMetadata::normalizeTags($data['tags'] ?? []),
                        ],
                    );

                    app(WorkflowNotificationService::class)->automatic(
                        area: AdminAccess::MEDIA_LIBRARY,
                        trigger: WorkflowNotificationRule::TRIGGER_UPDATED,
                        recordKey: 'media-library:'.$path,
                        recordLabel: basename($path),
                        adminUrl: static::getUrl(),
                    );

                    Notification::make()
                        ->title('Image details saved')
                        ->success()
                        ->send();
                }),
            Heroicon::OutlinedTag,
        );
    }

    protected function deleteImageAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('deleteImage')
                ->label('Delete image')
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
                    MediaImageMetadata::query()->where('path', $path)->delete();

                    app(WorkflowNotificationService::class)->automatic(
                        area: AdminAccess::MEDIA_LIBRARY,
                        trigger: WorkflowNotificationRule::TRIGGER_DELETED,
                        recordKey: 'media-library:'.$path,
                        recordLabel: basename($path),
                        adminUrl: static::getUrl(),
                    );

                    Notification::make()
                        ->title('Image deleted')
                        ->success()
                        ->send();
                }),
            Heroicon::OutlinedTrash,
        );
    }

    private function imageMetadataFormData(string $path): array
    {
        $metadata = MediaImageMetadata::query()->firstWhere('path', $path);

        return [
            'title' => $metadata?->title,
            'slug' => $metadata?->slug,
            'tags' => $metadata?->tags ?? [],
        ];
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
            $image['title'] ?? null,
            $image['slug'] ?? null,
            ...($image['tags'] ?? []),
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

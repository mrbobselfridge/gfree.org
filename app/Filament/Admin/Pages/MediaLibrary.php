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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MediaLibrary extends Page
{
    use RequiresAdminPageAccess;

    private const IMAGE_DIRECTORY = 'media-library';

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
            ->label('Upload image')
            ->color('success')
            ->modalHeading('Upload image')
            ->modalSubmitActionLabel('Upload image')
            ->schema([
                ...$this->imageUploadFields('image', 'Image'),
                ...$this->imageMetadataFields(visibleAfterUploadField: 'image'),
            ])
            ->action(function (array $data): void {
                $path = $this->firstUploadedImagePath($data['image'] ?? null);

                if (blank($path)) {
                    return;
                }

                $this->saveImageMetadata(
                    path: $path,
                    data: $data,
                    fallbackTitle: $this->titleFromUploadedFilename($data['image_original_name'] ?? null, $path),
                    fallbackSlug: $this->slugFromUploadedFilename($data['image_original_name'] ?? null, $path),
                );
                $this->notifyMediaLibraryWorkflow(
                    trigger: WorkflowNotificationRule::TRIGGER_CREATED,
                    path: $path,
                );

                Notification::make()
                    ->title('Image uploaded')
                    ->success()
                    ->send();
            });
    }

    protected function editImageMetadataAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('editImageMetadata')
                ->label('Edit image')
                ->modalHeading('Edit image')
                ->modalDescription('Update image details, or upload a replacement image to update every tracked place using this image.')
                ->modalSubmitActionLabel('Save image')
                ->fillForm(fn (array $arguments): array => $this->imageMetadataFormData((string) ($arguments['path'] ?? '')))
                ->schema([
                    ...$this->imageMetadataFields(),
                    ...$this->currentImagePreviewFields(),
                    ...$this->imageUploadFields('replacement_image', 'Replacement image', required: false),
                ])
                ->action(function (array $arguments, array $data): void {
                    $path = (string) ($arguments['path'] ?? '');

                    if (blank($path)) {
                        return;
                    }

                    $newPath = $this->firstUploadedImagePath($data['replacement_image'] ?? null);

                    if (filled($newPath)) {
                        $updated = $this->replaceStoredImage(
                            oldPath: $path,
                            newPath: $newPath,
                            data: $data,
                            fallbackTitle: $this->titleFromUploadedFilename($data['replacement_image_original_name'] ?? null, $newPath),
                            fallbackSlug: $this->slugFromUploadedFilename($data['replacement_image_original_name'] ?? null, $newPath),
                        );

                        Notification::make()
                            ->title('Image saved')
                            ->body("Replaced the image and updated {$updated} tracked ".str('location')->plural($updated).'.')
                            ->success()
                            ->send();

                        return;
                    }

                    $this->saveImageMetadata($path, $data);
                    $this->notifyMediaLibraryWorkflow(
                        trigger: WorkflowNotificationRule::TRIGGER_UPDATED,
                        path: $path,
                    );

                    Notification::make()
                        ->title('Image saved')
                        ->success()
                        ->send();
                }),
            Heroicon::OutlinedPencilSquare,
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
            'current_image' => $path,
            'title' => $metadata?->title,
            'slug' => $metadata?->slug,
            'existing_title' => $metadata?->title,
            'existing_slug' => $metadata?->slug,
            'existing_path' => $metadata?->path,
            'tags' => $metadata?->tags ?? [],
        ];
    }

    /**
     * @return array<int, TextInput|TagsInput>
     */
    private function imageMetadataFields(?string $visibleAfterUploadField = null): array
    {
        return [
            TextInput::make('title')
                ->label('Title')
                ->helperText('Leave blank to use the uploaded filename without the extension.')
                ->live(onBlur: true)
                ->visible(fn (Get $get): bool => $this->shouldShowMetadataFields($visibleAfterUploadField, $get))
                ->maxLength(255)
                ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                    $this->mergeAutoTagsIntoForm($set, $get, $state);
                }),
            TextInput::make('existing_title')
                ->hidden(),
            TagsInput::make('tags')
                ->label('Tags')
                ->placeholder('Add tag')
                ->suggestions(fn (): array => array_values(MediaLibrarySupport::tagOptions()))
                ->visible(fn (Get $get): bool => $this->shouldShowMetadataFields($visibleAfterUploadField, $get))
                ->splitKeys(['Tab', ','])
                ->reorderable()
                ->nestedRecursiveRules(['max:80']),
            TextInput::make('slug')
                ->label('Optional Slug / Path')
                ->helperText('Optional. Leave blank to use the uploaded filename. Slashes are allowed for grouped paths.')
                ->visible(fn (Get $get): bool => $this->shouldShowMetadataFields($visibleAfterUploadField, $get))
                ->maxLength(255)
                ->dehydrateStateUsing(fn (?string $state): ?string => MediaImageMetadata::normalizeSlug($state)),
            TextInput::make('existing_slug')
                ->hidden(),
            TextInput::make('existing_path')
                ->hidden(),
        ];
    }

    /**
     * @return array<int, FileUpload>
     */
    private function currentImagePreviewFields(): array
    {
        return [
            FileUpload::make('current_image')
                ->label('Current image')
                ->image()
                ->disk('public')
                ->downloadable()
                ->openable()
                ->deletable(false)
                ->dehydrated(false)
                ->helperText('Shown for reference. Use Replacement image below only if this image should be swapped everywhere it is tracked.'),
        ];
    }

    /**
     * @return array<int, FileUpload|TextInput>
     */
    private function imageUploadFields(string $name, string $label, bool $required = true): array
    {
        $originalNameField = $this->originalFileNameField($name);

        $upload = FileUpload::make($name)
            ->label($label)
            ->image()
            ->disk('public')
            ->directory(self::IMAGE_DIRECTORY)
            ->storeFileNamesIn($originalNameField)
            ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => $this->storedImageFilename($file));

        $upload->afterStateUpdated(function (Set $set, Get $get, mixed $state) use ($originalNameField): void {
            $path = $this->firstUploadedImagePath($state);

            if (blank($path)) {
                return;
            }

            $title = $this->titleFromUploadedFilename($get($originalNameField), $state);
            $slug = $this->slugFromUploadedFilename($get($originalNameField), $state);

            if (filled($title) && $this->shouldUseUploadedFilenameValue($get('title'), $get('existing_title'))) {
                $set('title', $title);
            }

            if (filled($slug) && $this->shouldUseUploadedFilenameValue(
                MediaImageMetadata::normalizeSlug($get('slug')),
                MediaImageMetadata::normalizeSlug($get('existing_slug')),
            )) {
                $set('slug', $this->uniqueFallbackSlug(
                    slug: $slug,
                    ignorePath: filled($get('existing_path')) ? (string) $get('existing_path') : $path,
                ));
            }

            $this->mergeAutoTagsIntoForm($set, $get, $title ?: $get('title'));
        });

        if ($required) {
            $upload->required();
        } else {
            $upload->helperText('Optional. Uploading here replaces this image everywhere it is tracked.');
        }

        return [
            $upload,
            TextInput::make($originalNameField)
                ->hidden(),
        ];
    }

    private function originalFileNameField(string $name): string
    {
        return $name.'_original_name';
    }

    private function storedImageFilename(TemporaryUploadedFile $file): string
    {
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = str($baseName)->slug()->toString() ?: 'image';
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg';

        return str(Str::ulid().'/'.$slug.'.'.$extension)->lower()->toString();
    }

    private function saveImageMetadata(
        string $path,
        array $data,
        ?string $ignorePath = null,
        ?string $fallbackTitle = null,
        ?string $fallbackSlug = null,
    ): void
    {
        MediaImageMetadata::query()->updateOrCreate(
            ['path' => $path],
            $this->normalizedImageMetadataData(
                data: $data,
                ignorePath: $ignorePath ?? $path,
                fallbackTitle: $fallbackTitle,
                fallbackSlug: $fallbackSlug,
            ),
        );
    }

    /**
     * @return array{title: ?string, slug: ?string, tags: array<int, string>}
     */
    private function normalizedImageMetadataData(
        array $data,
        string $ignorePath,
        ?string $fallbackTitle = null,
        ?string $fallbackSlug = null,
        ?string $replaceTitle = null,
        ?string $replaceSlug = null,
    ): array
    {
        $submittedSlug = MediaImageMetadata::normalizeSlug($data['slug'] ?? null);
        $slug = ($submittedSlug === null || ($replaceSlug !== null && $submittedSlug === $replaceSlug))
            ? $this->uniqueFallbackSlug($fallbackSlug, $ignorePath)
            : $submittedSlug;
        $submittedTitle = filled($data['title'] ?? null) ? trim((string) $data['title']) : null;
        $title = ($submittedTitle === null || ($replaceTitle !== null && $submittedTitle === $replaceTitle))
            ? $fallbackTitle
            : $submittedTitle;

        validator(
            ['slug' => $slug],
            [
                'slug' => [
                    'nullable',
                    'max:255',
                    Rule::unique('media_image_metadata', 'slug')->ignore($ignorePath, 'path'),
                ],
            ],
        )->validate();

        return [
            'title' => $title,
            'slug' => $slug,
            'tags' => MediaImageMetadata::mergeAutoTags($data['tags'] ?? [], $title),
        ];
    }

    private function firstUploadedImagePath(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = collect($path)->first();
        }

        return filled($path) ? (string) $path : null;
    }

    private function replaceStoredImage(
        string $oldPath,
        string $newPath,
        array $data,
        ?string $fallbackTitle = null,
        ?string $fallbackSlug = null,
    ): int
    {
        $existingMetadata = MediaImageMetadata::query()->firstWhere('path', $oldPath);
        $metadata = $this->normalizedImageMetadataData(
            data: $data,
            ignorePath: $oldPath,
            fallbackTitle: $fallbackTitle,
            fallbackSlug: $fallbackSlug,
            replaceTitle: $existingMetadata?->title,
            replaceSlug: $existingMetadata?->slug,
        );
        $updated = MediaUsage::replaceImagePath($oldPath, $newPath);

        MediaImageMetadata::query()
            ->where('path', $oldPath)
            ->update(['path' => $newPath]);

        MediaImageMetadata::query()->updateOrCreate(
            ['path' => $newPath],
            $metadata,
        );

        Storage::disk('public')->delete($oldPath);

        $this->notifyMediaLibraryWorkflow(
            trigger: WorkflowNotificationRule::TRIGGER_UPDATED,
            path: $oldPath,
        );

        return $updated;
    }

    private function titleFromUploadedFilename(mixed $originalName, mixed $path): ?string
    {
        $title = str($this->uploadedFilenameStem($originalName, $path))
            ->replaceMatches('/^[0-9a-hjkmnp-tv-z]{26}[\s_.-]+/i', '')
            ->replaceMatches('/[\s_.-]+/', ' ')
            ->trim()
            ->headline()
            ->toString();

        return filled($title) ? $title : null;
    }

    private function slugFromUploadedFilename(mixed $originalName, mixed $path): ?string
    {
        $stem = str($this->uploadedFilenameStem($originalName, $path))
            ->replaceMatches('/^[0-9a-hjkmnp-tv-z]{26}[\s_.-]+/i', '')
            ->toString();

        return MediaImageMetadata::normalizeSlug($stem);
    }

    private function uploadedFilenameStem(mixed $originalName, mixed $path): string
    {
        $source = is_array($originalName) ? collect($originalName)->first() : $originalName;

        if (blank($source)) {
            $source = $this->originalNameFromUploadState($path);
        }

        $source = filled($source) ? (string) $source : 'image';

        return pathinfo($source, PATHINFO_FILENAME);
    }

    private function originalNameFromUploadState(mixed $state): ?string
    {
        if (is_array($state)) {
            $state = collect($state)->first();
        }

        if ($state instanceof TemporaryUploadedFile) {
            return $state->getClientOriginalName();
        }

        return filled($state) ? basename((string) $state) : null;
    }

    private function uniqueFallbackSlug(?string $slug, string $ignorePath): ?string
    {
        if (blank($slug)) {
            return null;
        }

        $baseSlug = $slug;
        $candidate = $baseSlug;
        $suffix = 2;

        while (MediaImageMetadata::query()
            ->where('slug', $candidate)
            ->where('path', '!=', $ignorePath)
            ->exists()) {
            $candidate = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function shouldUseUploadedFilenameValue(mixed $currentValue, mixed $existingValue): bool
    {
        $currentValue = filled($currentValue) ? trim((string) $currentValue) : null;
        $existingValue = filled($existingValue) ? trim((string) $existingValue) : null;

        return $currentValue === null || ($existingValue !== null && $currentValue === $existingValue);
    }

    private function mergeAutoTagsIntoForm(Set $set, Get $get, ?string $title): void
    {
        $set('tags', MediaImageMetadata::mergeAutoTags($get('tags') ?? [], $title));
    }

    private function shouldShowMetadataFields(?string $visibleAfterUploadField, Get $get): bool
    {
        if ($visibleAfterUploadField === null) {
            return true;
        }

        return filled($this->firstUploadedImagePath($get($visibleAfterUploadField)));
    }

    private function notifyMediaLibraryWorkflow(string $trigger, string $path): mixed
    {
        return app(WorkflowNotificationService::class)->automatic(
            area: AdminAccess::MEDIA_LIBRARY,
            trigger: $trigger,
            recordKey: 'media-library:'.$path,
            recordLabel: basename($path),
            adminUrl: static::getUrl(),
        );
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

<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\HasCentralizedAdminNavigation;
use App\Filament\Admin\Forms\Components\UnsplashImagePicker;
use App\Filament\Admin\Forms\InternalNotes;
use App\Filament\Admin\Pages\Concerns\RequiresAdminPageAccess;
use App\Filament\Admin\Support\IconOnlyAction;
use App\Models\MediaImageMetadata;
use App\Models\WorkflowNotificationRule;
use App\Support\AdminAccess;
use App\Support\MediaLibrary as MediaLibrarySupport;
use App\Support\MediaTagOptions;
use App\Support\MediaUsage;
use App\Support\UnsplashImageImporter;
use App\Support\UploadedFilenameTitle;
use App\Support\WorkflowNotificationService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MediaLibrary extends Page
{
    use HasCentralizedAdminNavigation;
    use RequiresAdminPageAccess;

    private const IMAGE_DIRECTORY = 'media-library';

    private const IMAGE_BATCH_SIZE = 100000;

    protected static ?string $title = 'Media Library';

    protected static ?string $slug = 'media-library';

    protected string $view = 'filament.admin.pages.media-library';

    public string $search = '';

    public string $sort = 'recent';

    public int $imageLimit = self::IMAGE_BATCH_SIZE;

    /**
     * @var array<int, string>
     */
    public array $selectedImages = [];

    public function updatedSearch(): void
    {
        $this->search = trim($this->search);
        $this->resetImageLimit();
        $this->clearSelectedImages();
    }

    public function updatedSort(): void
    {
        $this->resetImageLimit();
        $this->clearSelectedImages();
    }

    public function loadMoreImages(): void
    {
        $this->imageLimit += self::IMAGE_BATCH_SIZE;
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
        return $this->getImageResults()['items'];
    }

    public function getTotalImageCount(): int
    {
        return $this->getImageResults()['total'];
    }

    public function getFilteredImageCount(): int
    {
        return $this->getImageResults()['filtered_total'];
    }

    public function hasMoreImages(): bool
    {
        return $this->getImageResults()['has_more'];
    }

    public function getImageResults(): array
    {
        return MediaLibrarySupport::pagedImages(
            search: $this->search,
            sort: $this->sort,
            limit: $this->imageLimit,
        );
    }

    public function getSortOptions(): array
    {
        return MediaLibrarySupport::sortOptions();
    }

    public function editImageMetadataClickHandler(string $path): ?string
    {
        return $this->editImageMetadataAction()(['path' => $path])->getLivewireClickHandler();
    }

    public function deleteImageClickHandler(string $path): ?string
    {
        return $this->deleteImageAction()(['path' => $path])->getLivewireClickHandler();
    }

    public function getSelectedImageCount(): int
    {
        return count($this->selectedImagePaths());
    }

    public function allShownImagesSelected(): bool
    {
        $shownPaths = $this->shownImagePaths();

        if ($shownPaths === []) {
            return false;
        }

        return empty(array_diff($shownPaths, $this->selectedImagePaths()));
    }

    public function selectShownImages(): void
    {
        $this->selectedImages = $this->shownImagePaths();
    }

    public function clearSelectedImages(): void
    {
        $this->selectedImages = [];
    }

    public function toggleShownImages(): void
    {
        if ($this->allShownImagesSelected()) {
            $this->clearSelectedImages();

            return;
        }

        $this->selectShownImages();
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
                $this->resetImageLimit();
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

    protected function importUnsplashImageAction(): Action
    {
        return Action::make('importUnsplashImage')
            ->label('Import from Unsplash')
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->modalHeading('Import from Unsplash')
            ->modalSubmitAction(false)
            ->modalWidth(Width::Screen)
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->schema([
                TextInput::make('unsplash_search')
                    ->label('Search Unsplash')
                    ->placeholder('Search for worship, family, community, kids...')
                    ->live(debounce: 400)
                    ->afterStateUpdated(fn (Set $set): mixed => $set('unsplash_page', 1))
                    ->dehydrated(false)
                    ->columnSpanFull(),
                Hidden::make('unsplash_page')
                    ->default(1)
                    ->dehydrated(false),
                UnsplashImagePicker::make('unsplash_photo_id')
                    ->label('Unsplash photos')
                    ->required()
                    ->columnSpanFull(),
            ])
            ->fillForm([
                'unsplash_search' => null,
                'unsplash_page' => 1,
                'unsplash_photo_id' => null,
            ])
            ->action(function (array $data): void {
                $photoId = filled($data['unsplash_photo_id'] ?? null) ? (string) $data['unsplash_photo_id'] : null;

                if ($photoId === null) {
                    Notification::make()
                        ->title('Choose an Unsplash photo first')
                        ->warning()
                        ->send();

                    return;
                }

                try {
                    /** @var UnsplashImageImporter $importer */
                    $importer = app(UnsplashImageImporter::class);
                    $import = $importer->import($photoId, self::IMAGE_DIRECTORY, $this->currentUserId());
                } catch (\Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title('Unsplash import failed')
                        ->body('Check the Unsplash API key and try again.')
                        ->danger()
                        ->send();

                    return;
                }

                $this->resetImageLimit();
                $this->notifyMediaLibraryWorkflow(
                    trigger: WorkflowNotificationRule::TRIGGER_CREATED,
                    path: $import['path'],
                );

                Notification::make()
                    ->title('Unsplash photo imported')
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
                    InternalNotes::field(visibleOnEditOnly: false),
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

                    $result = $this->deleteStoredImages([$path]);
                    $clearedRecords = $result['cleared_records'];

                    Notification::make()
                        ->title($clearedRecords > 0 ? 'Image deleted and removed from content' : 'Image deleted')
                        ->success()
                        ->send();
                }),
            Heroicon::OutlinedTrash,
        );
    }

    protected function deleteSelectedImagesAction(): Action
    {
        return Action::make('deleteSelectedImages')
            ->label('Delete selected images')
            ->color('danger')
            ->disabled(fn (): bool => $this->getSelectedImageCount() === 0)
            ->requiresConfirmation()
            ->modalHeading('Delete selected images')
            ->modalDescription(fn (): string => $this->deleteSelectedImagesDescription())
            ->modalSubmitActionLabel('Delete selected')
            ->action(function (): void {
                $paths = $this->selectedImagePaths();

                if ($paths === []) {
                    Notification::make()
                        ->title('No images selected')
                        ->warning()
                        ->send();

                    return;
                }

                $result = $this->deleteStoredImages($paths);
                $deleted = $result['deleted_images'];
                $clearedRecords = $result['cleared_records'];

                Notification::make()
                    ->title($deleted === 1 ? 'Image deleted' : "{$deleted} images deleted")
                    ->body($clearedRecords > 0 ? "Removed from {$clearedRecords} tracked ".str('record')->plural($clearedRecords).'.' : null)
                    ->success()
                    ->send();
            });
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
            'notes' => $metadata?->notes,
        ];
    }

    /**
     * @return array<int, TextInput|Select>
     */
    private function imageMetadataFields(?string $visibleAfterUploadField = null): array
    {
        return [
            TextInput::make('title')
                ->label('Image title')
                ->helperText('Leave blank to use the uploaded filename without the extension.')
                ->live(onBlur: true)
                ->visible(fn (Get $get): bool => $this->shouldShowMetadataFields($visibleAfterUploadField, $get))
                ->maxLength(255)
                ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?string $old): void {
                    $this->refreshGeneratedSlugForTitle($set, $get, $state, $old);
                    $this->refreshAutoTagsInForm($set, $get, $state, $old);
                }),
            TextInput::make('existing_title')
                ->hidden(),
            Select::make('tags')
                ->label('Tags')
                ->placeholder('Add tag')
                ->options(fn (Select $component): array => MediaTagOptions::optionsWithSelected($component->getState() ?? []))
                ->getOptionLabelsUsing(fn (Select $component): array => MediaTagOptions::labelsFor($component->getState() ?? []))
                ->createOptionForm([
                    TextInput::make('tag')
                        ->label('Tag')
                        ->required()
                        ->maxLength(80),
                ])
                ->createOptionUsing(fn (array $data): string => MediaTagOptions::normalizeCreatedTag($data['tag'] ?? null))
                ->createOptionModalHeading('Add tag')
                ->multiple()
                ->searchable()
                ->preload()
                ->native(false)
                ->in(fn (Select $component): array => MediaTagOptions::validationValues($component->getState() ?? []))
                ->visible(fn (Get $get): bool => $this->shouldShowMetadataFields($visibleAfterUploadField, $get))
                ->nestedRecursiveRules(['max:80']),
            TextInput::make('slug')
                ->label('Image path')
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
    ): void {
        $metadata = MediaImageMetadata::query()->firstOrNew(['path' => $path]);

        if (! $metadata->exists) {
            $metadata->created_by_user_id = $this->currentUserId();
        }

        $metadata->fill(
            $this->normalizedImageMetadataData(
                data: $data,
                ignorePath: $ignorePath ?? $path,
                fallbackTitle: $fallbackTitle,
                fallbackSlug: $fallbackSlug,
            )
        );
        $metadata->save();
        MediaLibrarySupport::clearImageIndexCache();
    }

    /**
     * @return array{title: ?string, slug: ?string, tags: array<int, string>, notes: ?string}
     */
    private function normalizedImageMetadataData(
        array $data,
        string $ignorePath,
        ?string $fallbackTitle = null,
        ?string $fallbackSlug = null,
        ?string $replaceTitle = null,
        ?string $replaceSlug = null,
    ): array {
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
            'notes' => $data['notes'] ?? null,
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
    ): int {
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

        $replacementMetadata = MediaImageMetadata::query()->firstOrNew(['path' => $newPath]);

        if (! $replacementMetadata->exists) {
            $replacementMetadata->created_by_user_id = $existingMetadata?->created_by_user_id ?? $this->currentUserId();
        }

        $replacementMetadata->fill($metadata);
        $replacementMetadata->save();

        Storage::disk('public')->delete($oldPath);
        MediaLibrarySupport::clearImageIndexCache();
        $this->resetImageLimit();

        $this->notifyMediaLibraryWorkflow(
            trigger: WorkflowNotificationRule::TRIGGER_UPDATED,
            path: $oldPath,
        );

        return $updated;
    }

    private function titleFromUploadedFilename(mixed $originalName, mixed $path): ?string
    {
        $stem = str($this->uploadedFilenameStem($originalName, $path))
            ->replaceMatches('/^[0-9a-hjkmnp-tv-z]{26}[\s_.-]+/i', '')
            ->toString();

        return UploadedFilenameTitle::fromStem($stem);
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

    private function refreshAutoTagsInForm(Set $set, Get $get, ?string $title, ?string $previousTitle): void
    {
        $set('tags', MediaImageMetadata::refreshAutoTags($get('tags') ?? [], $previousTitle, $title));
    }

    private function refreshGeneratedSlugForTitle(Set $set, Get $get, ?string $title, ?string $previousTitle): void
    {
        $slug = MediaImageMetadata::normalizeSlug($title);

        if (blank($slug)) {
            return;
        }

        $currentSlug = MediaImageMetadata::normalizeSlug($get('slug'));
        $previousGeneratedSlug = MediaImageMetadata::normalizeSlug($previousTitle);

        if ($currentSlug !== null && $previousGeneratedSlug !== null && $currentSlug !== $previousGeneratedSlug) {
            return;
        }

        $set('slug', $this->uniqueFallbackSlug(
            slug: $slug,
            ignorePath: filled($get('existing_path')) ? (string) $get('existing_path') : ($this->firstUploadedImagePath($get('image')) ?: $slug),
        ));
    }

    private function currentUserId(): ?int
    {
        $user = Filament::auth()->user();

        return $user ? (int) $user->getKey() : null;
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

        return "Warning: this image is currently used in {$usedIn}. Deleting it will clear this image from those places so their defaults or blank image states can apply.";
    }

    private function deleteSelectedImagesDescription(): string
    {
        $count = $this->getSelectedImageCount();

        if ($count === 0) {
            return 'Select one or more images before using bulk delete.';
        }

        return "Delete {$count} selected ".str('image')->plural($count).'? Any tracked page, banner, card, or content block image selections using them will be cleared first.';
    }

    /**
     * @param  array<int, string>  $paths
     * @return array{deleted_images: int, cleared_records: int}
     */
    private function deleteStoredImages(array $paths): array
    {
        $paths = collect($paths)
            ->map(fn (mixed $path): string => trim((string) $path))
            ->filter()
            ->unique()
            ->values();

        if ($paths->isEmpty()) {
            return [
                'deleted_images' => 0,
                'cleared_records' => 0,
            ];
        }

        $clearedRecords = 0;

        $paths->each(function (string $path) use (&$clearedRecords): void {
            $clearedRecords += MediaUsage::clearImagePath($path);

            Storage::disk('public')->delete($path);
            MediaImageMetadata::query()->where('path', $path)->delete();

            app(WorkflowNotificationService::class)->automatic(
                area: AdminAccess::MEDIA_LIBRARY,
                trigger: WorkflowNotificationRule::TRIGGER_DELETED,
                recordKey: 'media-library:'.$path,
                recordLabel: basename($path),
                adminUrl: static::getUrl(),
            );
        });

        MediaLibrarySupport::clearImageIndexCache();
        $this->resetImageLimit();
        $this->clearSelectedImages();

        return [
            'deleted_images' => $paths->count(),
            'cleared_records' => $clearedRecords,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function selectedImagePaths(): array
    {
        return collect($this->selectedImages)
            ->map(fn (mixed $path): string => trim((string) $path))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function shownImagePaths(): array
    {
        return $this->getImages()
            ->pluck('path')
            ->map(fn (mixed $path): string => (string) $path)
            ->filter()
            ->values()
            ->all();
    }

    private function resetImageLimit(): void
    {
        $this->imageLimit = self::IMAGE_BATCH_SIZE;
    }
}

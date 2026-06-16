<?php

namespace App\Filament\Admin\Forms;

use App\Filament\Admin\Support\IconOnlyAction;
use App\Filament\Admin\Forms\Components\ImageGalleryPicker;
use App\Models\MediaImageMetadata;
use App\Support\MediaLibrary as MediaLibrarySupport;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageUpload
{
    /**
     * @return array<int, ViewField>
     */
    public static function make(string $name, string $directory, ?string $label = null, ?Closure $configureUpload = null): array
    {
        $selector = ViewField::make($name)
            ->view('filament.admin.forms.components.image-selector')
            ->viewData(fn (ViewField $component): array => self::selectorViewData($component))
            ->dehydrateStateUsing(fn (mixed $state): ?string => self::selectedImagePath($state))
            ->registerActions([
                self::chooseExistingImageAction(),
                self::openImageAction(),
                self::detachImageAction(),
                self::addImageAction($directory),
                self::editImageAction($directory),
            ])
            ->partiallyRenderAfterStateUpdated();

        if ($label) {
            $selector->label($label);
        }

        if ($configureUpload) {
            $selector = $configureUpload($selector) ?? $selector;
        }

        return [
            self::configure($selector),
        ];
    }

    public static function configure(ViewField $selector): ViewField
    {
        return $selector;
    }

    private static function chooseExistingImageAction(): Action
    {
        return self::selectorIconAction(
            Action::make('chooseExistingImage')
                ->label('Choose existing image')
                ->icon(Heroicon::OutlinedPhoto)
                ->modalHeading('Choose an existing image')
                ->modalSubmitAction(false)
                ->modalWidth(Width::Screen)
                ->stickyModalHeader()
                ->stickyModalFooter()
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'lg' => 2,
                    ])
                        ->schema([
                            TextInput::make('existing_image_search')
                                ->label('Search')
                                ->placeholder('Search path, filename, title, tag, or content area')
                                ->live(debounce: 300)
                                ->dehydrated(false)
                                ->afterStateUpdated(fn (Set $set): mixed => $set('existing_image_limit', ImageGalleryPicker::DEFAULT_LIMIT)),
                            Select::make('existing_image_sort')
                                ->label('Sort by')
                                ->options(MediaLibrarySupport::sortOptions())
                                ->default('recent')
                                ->live()
                                ->dehydrated(false)
                                ->afterStateUpdated(fn (Set $set): mixed => $set('existing_image_limit', ImageGalleryPicker::DEFAULT_LIMIT)),
                            Hidden::make('existing_image_limit')
                                ->default(ImageGalleryPicker::DEFAULT_LIMIT)
                                ->dehydrated(false)
                                ->columnSpanFull(),
                            ImageGalleryPicker::make('existing_image_path')
                                ->label('Images')
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->columnSpanFull(),
                ])
                ->fillForm(fn (ViewField $component): array => [
                    'existing_image_path' => self::selectedImagePath($component->getState()),
                    'existing_image_search' => null,
                    'existing_image_sort' => 'recent',
                    'existing_image_limit' => ImageGalleryPicker::DEFAULT_LIMIT,
                ])
                ->action(function (array $data, ViewField $component): void {
                    $component->state(self::selectedImagePath($data['existing_image_path'] ?? null));
                    $component->callAfterStateUpdated();
                }),
        );
    }

    private static function openImageAction(): Action
    {
        return self::selectorIconAction(
            Action::make('openImage')
                ->label('Open image')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->disabled(fn (ViewField $component): bool => blank(self::selectedImageUrl(self::selectedImagePath($component->getState()))))
                ->url(fn (ViewField $component): ?string => self::selectedImageUrl(self::selectedImagePath($component->getState())))
                ->openUrlInNewTab(),
        );
    }

    private static function detachImageAction(): Action
    {
        return self::selectorIconAction(
            Action::make('detachImage')
                ->label('Remove image')
                ->icon(Heroicon::OutlinedXMark)
                ->color('danger')
                ->disabled(fn (ViewField $component): bool => blank(self::selectedImagePath($component->getState())))
                ->action(function (ViewField $component): void {
                    $component->state(null);
                    $component->callAfterStateUpdated();
                }),
        );
    }

    private static function addImageAction(string $directory): Action
    {
        return self::selectorIconAction(
            Action::make('addImage')
                ->label('Add image')
                ->icon(Heroicon::OutlinedPlus)
                ->modalHeading('Add image')
                ->modalSubmitActionLabel('Upload image')
                ->visible(fn (ViewField $component): bool => blank(self::selectedImagePath($component->getState())))
                ->schema([
                    ...self::imageUploadFields('new_image', 'Image', $directory),
                    ...self::modalMetadataFields(visibleAfterUploadField: 'new_image'),
                ])
                ->action(function (array $data, ViewField $component): void {
                    $path = self::selectedImagePath($data['new_image'] ?? null);

                    if (blank($path)) {
                        Notification::make()
                            ->title('Choose an image first')
                            ->warning()
                            ->send();

                        return;
                    }

                    self::saveMetadataForPath(
                        path: $path,
                        data: $data,
                        markCreatorForNew: true,
                        fallbackTitle: self::titleFromUploadedFilename($data['new_image_original_name'] ?? null, $data['new_image'] ?? $path),
                        fallbackSlug: self::slugFromUploadedFilename($data['new_image_original_name'] ?? null, $data['new_image'] ?? $path),
                    );

                    $component->state($path);
                    $component->callAfterStateUpdated();

                    Notification::make()
                        ->title('Image uploaded')
                        ->success()
                        ->send();
                }),
        );
    }

    private static function editImageAction(string $directory): Action
    {
        return self::selectorIconAction(
            Action::make('editImage')
                ->label('Edit image')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->modalHeading('Edit image')
                ->modalDescription('Update image details, or upload a replacement image for this field only.')
                ->modalSubmitActionLabel('Save image')
                ->visible(fn (ViewField $component): bool => filled(self::selectedImagePath($component->getState())))
                ->fillForm(fn (ViewField $component): array => self::metadataFormData(self::selectedImagePath($component->getState())))
                ->schema([
                    ...self::modalMetadataFields(),
                    ...self::currentImagePreviewFields(),
                    ...self::imageUploadFields('replacement_image', 'Replacement image', $directory, required: false),
                ])
                ->action(function (array $data, ViewField $component): void {
                    $path = self::selectedImagePath($component->getState());

                    if (blank($path)) {
                        Notification::make()
                            ->title('Choose an image first')
                            ->warning()
                            ->send();

                        return;
                    }

                    $newPath = self::selectedImagePath($data['replacement_image'] ?? null);

                    if (filled($newPath)) {
                        self::saveMetadataForPath(
                            path: $newPath,
                            data: $data,
                            markCreatorForNew: true,
                            fallbackTitle: self::titleFromUploadedFilename($data['replacement_image_original_name'] ?? null, $data['replacement_image'] ?? $newPath),
                            fallbackSlug: self::slugFromUploadedFilename($data['replacement_image_original_name'] ?? null, $data['replacement_image'] ?? $newPath),
                            replaceTitle: filled($data['existing_title'] ?? null) ? (string) $data['existing_title'] : null,
                            replaceSlug: MediaImageMetadata::normalizeSlug($data['existing_slug'] ?? null),
                        );

                        $component->state($newPath);
                        $component->callAfterStateUpdated();

                        Notification::make()
                            ->title('Image saved')
                            ->success()
                            ->send();

                        return;
                    }

                    self::saveMetadataForPath($path, $data, markCreatorForNew: true);
                    $component->callAfterStateUpdated();

                    Notification::make()
                        ->title('Image saved')
                        ->success()
                        ->send();
                }),
        );
    }

    private static function selectorIconAction(Action $action): Action
    {
        return IconOnlyAction::make($action)
            ->size(Size::Large)
            ->extraAttributes(['class' => 'twyxtco-image-selector__icon-action'], merge: true);
    }

    /**
     * @return array<int, TextInput|TagsInput|Hidden>
     */
    private static function modalMetadataFields(?string $visibleAfterUploadField = null): array
    {
        return [
            TextInput::make('title')
                ->label('Title')
                ->helperText('Leave blank to use the uploaded filename without the extension.')
                ->live(onBlur: true)
                ->visible(fn (Get $get): bool => self::shouldShowMetadataFields($visibleAfterUploadField, $get))
                ->maxLength(255)
                ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                    self::mergeAutoTagsIntoForm($set, $get, $state);
                }),
            Hidden::make('existing_title'),
            TagsInput::make('tags')
                ->label('Tags')
                ->placeholder('Add tag')
                ->suggestions(fn (): array => array_values(MediaLibrarySupport::tagOptions()))
                ->visible(fn (Get $get): bool => self::shouldShowMetadataFields($visibleAfterUploadField, $get))
                ->splitKeys(['Tab', ','])
                ->reorderable()
                ->nestedRecursiveRules(['max:80']),
            TextInput::make('slug')
                ->label('Optional Slug / Path')
                ->helperText('Optional. Leave blank to use the uploaded filename. Slashes are allowed for grouped paths.')
                ->visible(fn (Get $get): bool => self::shouldShowMetadataFields($visibleAfterUploadField, $get))
                ->maxLength(255)
                ->dehydrateStateUsing(fn (?string $state): ?string => MediaImageMetadata::normalizeSlug($state)),
            Hidden::make('existing_slug'),
            Hidden::make('existing_path'),
        ];
    }

    /**
     * @return array<int, FileUpload>
     */
    private static function currentImagePreviewFields(): array
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
                ->helperText('Shown for reference. Use Replacement image below only if this field should use a different image.'),
        ];
    }

    /**
     * @return array<int, FileUpload|Hidden>
     */
    private static function imageUploadFields(string $name, string $label, string $directory, bool $required = true): array
    {
        $originalNameField = self::originalFileNameField($name);

        $upload = FileUpload::make($name)
            ->label($label)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->storeFileNamesIn($originalNameField)
            ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => self::storedImageFilename($file));

        $upload->afterStateUpdated(function (Set $set, Get $get, mixed $state) use ($originalNameField): void {
            if (! self::hasUploadedImage($state)) {
                return;
            }

            $title = self::titleFromUploadedFilename($get($originalNameField), $state);
            $slug = self::slugFromUploadedFilename($get($originalNameField), $state);

            if (filled($title) && self::shouldUseUploadedFilenameValue($get('title'), $get('existing_title'))) {
                $set('title', $title);
            }

            if (filled($slug) && self::shouldUseUploadedFilenameValue(
                MediaImageMetadata::normalizeSlug($get('slug')),
                MediaImageMetadata::normalizeSlug($get('existing_slug')),
            )) {
                $set('slug', self::uniqueSlug(
                    slug: $slug,
                    ignorePath: filled($get('existing_path')) ? (string) $get('existing_path') : ($get($originalNameField) ?: $slug),
                ));
            }

            self::mergeAutoTagsIntoForm($set, $get, $title ?: $get('title'));
        });

        if ($required) {
            $upload->required();
        } else {
            $upload->helperText('Optional. Uploading here replaces the image for this field only.');
        }

        return [
            $upload,
            Hidden::make($originalNameField),
        ];
    }

    private static function storedImageFilename(TemporaryUploadedFile $file): string
    {
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = str($baseName)->slug()->toString() ?: 'image';
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg';

        return str(Str::ulid().'/'.$slug.'.'.$extension)->lower()->toString();
    }

    private static function originalFileNameField(string $name): string
    {
        return $name.'_original_name';
    }

    private static function saveMetadataForPath(
        string $path,
        array $data,
        bool $markCreatorForNew = false,
        ?string $fallbackTitle = null,
        ?string $fallbackSlug = null,
        ?string $replaceTitle = null,
        ?string $replaceSlug = null,
    ): void {
        $metadata = MediaImageMetadata::query()->firstOrNew(['path' => $path]);
        $submittedTitle = filled($data['title'] ?? null) ? trim((string) $data['title']) : null;
        $title = ($submittedTitle === null || ($replaceTitle !== null && $submittedTitle === $replaceTitle))
            ? ($fallbackTitle ?: self::titleFromPath($path))
            : $submittedTitle;
        $submittedSlug = MediaImageMetadata::normalizeSlug($data['slug'] ?? null);
        $fallbackSlug ??= self::slugFromPath($path);
        $slug = ($submittedSlug === null || ($replaceSlug !== null && $submittedSlug === $replaceSlug))
            ? self::uniqueSlug($fallbackSlug, $path)
            : $submittedSlug;

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

        if (! $metadata->exists && $markCreatorForNew) {
            $metadata->created_by_user_id = self::currentUserId();
        }

        $metadata->fill([
            'title' => $title,
            'slug' => $slug,
            'tags' => MediaImageMetadata::mergeAutoTags($data['tags'] ?? [], $title),
        ]);
        $metadata->save();
        MediaLibrarySupport::clearImageIndexCache();
    }

    private static function metadataFormData(?string $path): array
    {
        $metadata = filled($path)
            ? MediaImageMetadata::query()->firstWhere('path', $path)
            : null;
        $title = $metadata?->title ?? (filled($path) ? self::titleFromPath($path) : null);
        $slug = $metadata?->slug ?? (filled($path) ? self::uniqueSlug(self::slugFromPath($path), $path) : null);

        return [
            'current_image' => $path,
            'title' => $title,
            'slug' => $slug,
            'existing_title' => $title,
            'existing_slug' => $slug,
            'existing_path' => $path,
            'tags' => $metadata?->tags ?? [],
        ];
    }

    private static function selectedImagePath(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = collect($path)->first();
        }

        return is_string($path) && filled($path) ? $path : null;
    }

    private static function hasUploadedImage(mixed $state): bool
    {
        if (is_array($state)) {
            $state = collect($state)->first();
        }

        return $state instanceof TemporaryUploadedFile || filled($state);
    }

    private static function titleFromUploadedFilename(mixed $originalName, mixed $path): ?string
    {
        $title = str(self::uploadedFilenameStem($originalName, $path))
            ->replaceMatches('/[\s_.-]+/', ' ')
            ->trim()
            ->headline()
            ->toString();

        return filled($title) ? $title : null;
    }

    private static function slugFromUploadedFilename(mixed $originalName, mixed $path): ?string
    {
        return MediaImageMetadata::normalizeSlug(self::uploadedFilenameStem($originalName, $path));
    }

    private static function uploadedFilenameStem(mixed $originalName, mixed $path): string
    {
        $source = is_array($originalName) ? collect($originalName)->first() : $originalName;

        if (blank($source)) {
            $source = self::originalNameFromUploadState($path);
        }

        $source = filled($source) ? (string) $source : 'image';

        return pathinfo($source, PATHINFO_FILENAME);
    }

    private static function originalNameFromUploadState(mixed $state): ?string
    {
        if (is_array($state)) {
            $state = collect($state)->first();
        }

        if ($state instanceof TemporaryUploadedFile) {
            return $state->getClientOriginalName();
        }

        return is_string($state) && filled($state) ? basename($state) : null;
    }

    private static function titleFromPath(string $path): ?string
    {
        $title = str(pathinfo($path, PATHINFO_FILENAME))
            ->replaceMatches('/^[0-9a-hjkmnp-tv-z]{26}[\s_.-]+/i', '')
            ->replaceMatches('/[\s_.-]+/', ' ')
            ->trim()
            ->headline()
            ->toString();

        return filled($title) ? $title : null;
    }

    private static function slugFromPath(string $path): ?string
    {
        $stem = str(pathinfo($path, PATHINFO_FILENAME))
            ->replaceMatches('/^[0-9a-hjkmnp-tv-z]{26}[\s_.-]+/i', '')
            ->toString();

        return MediaImageMetadata::normalizeSlug($stem);
    }

    private static function uniqueSlug(?string $slug, string $ignorePath): ?string
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

    private static function shouldUseUploadedFilenameValue(mixed $currentValue, mixed $existingValue): bool
    {
        $currentValue = filled($currentValue) ? trim((string) $currentValue) : null;
        $existingValue = filled($existingValue) ? trim((string) $existingValue) : null;

        return $currentValue === null || ($existingValue !== null && $currentValue === $existingValue);
    }

    private static function mergeAutoTagsIntoForm(Set $set, Get $get, ?string $title): void
    {
        $set('tags', MediaImageMetadata::mergeAutoTags($get('tags') ?? [], $title));
    }

    private static function shouldShowMetadataFields(?string $visibleAfterUploadField, Get $get): bool
    {
        if ($visibleAfterUploadField === null) {
            return true;
        }

        return self::hasUploadedImage($get($visibleAfterUploadField));
    }

    private static function selectorViewData(ViewField $component): array
    {
        $path = self::selectedImagePath($component->getState());
        $metadata = filled($path)
            ? MediaImageMetadata::query()->firstWhere('path', $path)
            : null;
        $url = self::selectedImageUrl($path);

        return [
            'selectedPath' => $path,
            'selectedImageExists' => filled($url),
            'selectedImageUrl' => $url,
            'selectedImageTitle' => $metadata?->title ?? (filled($path) ? self::titleFromPath($path) : null),
            'selectedImageTags' => $metadata?->tags ?? [],
        ];
    }

    private static function selectedImageUrl(?string $path): ?string
    {
        if (blank($path) || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    private static function currentUserId(): ?int
    {
        $user = Filament::auth()->user();

        return $user ? (int) $user->getKey() : null;
    }
}

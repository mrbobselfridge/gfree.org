<?php

namespace App\Filament\Admin\Forms;

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
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageUpload
{
    /**
     * @return array<int, FileUpload|TextInput|TagsInput>
     */
    public static function make(string $name, string $directory, ?string $label = null, ?Closure $configureUpload = null): array
    {
        $titleField = self::metadataFieldName($name, 'title');
        $slugField = self::metadataFieldName($name, 'slug');
        $tagsField = self::metadataFieldName($name, 'tags');

        $upload = FileUpload::make($name)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => self::storedImageFilename($file))
            ->afterStateUpdated(function (Set $set, Get $get, mixed $state) use ($name, $titleField, $slugField, $tagsField): void {
                $file = self::firstTemporaryUploadedFile($state);

                if (! $file) {
                    return;
                }

                $title = self::titleFromUploadedFile($file);
                $slug = self::uniqueSlug(self::slugFromUploadedFile($file), $name);

                if (filled($title) && blank($get($titleField))) {
                    $set($titleField, $title);
                }

                if (filled($slug) && blank(MediaImageMetadata::normalizeSlug($get($slugField)))) {
                    $set($slugField, $slug);
                }

                $set($tagsField, MediaImageMetadata::mergeAutoTags($get($tagsField) ?? [], $title));
            })
            ->saveUploadedFileUsing(function (FileUpload $component, TemporaryUploadedFile $file, Get $get) use ($name, $titleField, $slugField, $tagsField): ?string {
                $path = $component->saveUploadedFile($file);

                if (filled($path)) {
                    self::saveMetadataForPath(
                        path: $path,
                        data: [
                            'title' => $get($titleField) ?: self::titleFromUploadedFile($file),
                            'slug' => $get($slugField) ?: self::slugFromUploadedFile($file),
                            'tags' => $get($tagsField) ?? [],
                        ],
                        markCreatorForNew: true,
                    );
                }

                return $path;
            });

        if ($label) {
            $upload->label($label);
        }

        if ($configureUpload) {
            $upload = $configureUpload($upload) ?? $upload;
        }

        return [
            self::configure($upload),
            ...self::metadataFields($name, $titleField, $slugField, $tagsField),
        ];
    }

    public static function configure(FileUpload $upload): FileUpload
    {
        return $upload
            ->openable()
            ->downloadable()
            ->hintActions([
                Action::make('chooseExistingImage')
                    ->label('Choose existing')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->modalHeading('Choose an existing image1')
                    ->modalSubmitAction(false)
                    ->modalWidth(Width::Screen)
                    ->stickyModalHeader()
                    ->stickyModalFooter()
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
                            ->dehydrated(false),
                        ImageGalleryPicker::make('existing_image_path')
                            ->label('Images')
                            ->required(),
                    ])
                    ->fillForm(fn (FileUpload $component): array => [
                        'existing_image_path' => $component->getState(),
                        'existing_image_search' => null,
                        'existing_image_sort' => 'recent',
                        'existing_image_limit' => ImageGalleryPicker::DEFAULT_LIMIT,
                    ])
                    ->action(function (array $data, FileUpload $component): void {
                        $component->state($data['existing_image_path'] ?? null);
                        $component->callAfterStateUpdated();
                    }),
                Action::make('editImageDetails')
                    ->label('Image details')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->modalHeading('Image details')
                    ->modalSubmitActionLabel('Save details')
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
                            ->helperText('Leave blank to use the uploaded filename without the extension.')
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Optional Slug / Path')
                            ->helperText('Optional searchable path-style label. Slashes are allowed for grouped paths.')
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
                    ->fillForm(fn (FileUpload $component): array => self::metadataFormData(self::firstUploadedImagePath($component->getState())))
                    ->action(function (array $data, FileUpload $component): void {
                        $path = self::firstUploadedImagePath($component->getState());

                        if (blank($path)) {
                            Notification::make()
                                ->title('Choose or upload an image first')
                                ->warning()
                                ->send();

                            return;
                        }

                        self::saveMetadataForPath($path, $data);

                        Notification::make()
                            ->title('Image details saved')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    private static function storedImageFilename(TemporaryUploadedFile $file): string
    {
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = str($baseName)->slug()->toString() ?: 'image';
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg';

        return str(Str::ulid().'/'.$slug.'.'.$extension)->lower()->toString();
    }

    private static function metadataFieldName(string $name, string $field): string
    {
        return str($name)->replace('.', '_')->replace('*', 'item')->append('_media_', $field)->toString();
    }

    /**
     * @return array<int, TextInput|TagsInput>
     */
    private static function metadataFields(string $uploadField, string $titleField, string $slugField, string $tagsField): array
    {
        return [
            TextInput::make($titleField)
                ->label('Title')
                ->helperText('Leave blank to use the uploaded filename without the extension.')
                ->live(onBlur: true)
                ->visible(fn (Get $get): bool => self::hasTemporaryUpload($get($uploadField)))
                ->maxLength(255)
                ->dehydrated(false)
                ->afterStateUpdated(function (Set $set, Get $get, ?string $state) use ($tagsField): void {
                    $set($tagsField, MediaImageMetadata::mergeAutoTags($get($tagsField) ?? [], $state));
                }),
            TagsInput::make($tagsField)
                ->label('Tags')
                ->placeholder('Add tag')
                ->suggestions(fn (): array => array_values(MediaLibrarySupport::tagOptions()))
                ->visible(fn (Get $get): bool => self::hasTemporaryUpload($get($uploadField)))
                ->splitKeys(['Tab', ','])
                ->reorderable()
                ->nestedRecursiveRules(['max:80'])
                ->dehydrated(false),
            TextInput::make($slugField)
                ->label('Optional Slug / Path')
                ->helperText('Optional. Leave blank to use the uploaded filename. Slashes are allowed for grouped paths.')
                ->visible(fn (Get $get): bool => self::hasTemporaryUpload($get($uploadField)))
                ->maxLength(255)
                ->dehydrateStateUsing(fn (?string $state): ?string => MediaImageMetadata::normalizeSlug($state))
                ->dehydrated(false),
        ];
    }

    private static function saveDefaultMetadataForState(mixed $state): void
    {
        $path = self::firstUploadedImagePath($state);

        if (blank($path) || MediaImageMetadata::query()->where('path', $path)->exists()) {
            return;
        }

        $title = self::titleFromPath($path);

        self::saveMetadataForPath(
            path: $path,
            data: [
                'title' => $title,
                'slug' => self::slugFromPath($path),
                'tags' => [],
            ],
            markCreatorForNew: true,
        );
    }

    private static function saveMetadataForPath(string $path, array $data, bool $markCreatorForNew = false): void
    {
        $metadata = MediaImageMetadata::query()->firstOrNew(['path' => $path]);
        $submittedTitle = filled($data['title'] ?? null) ? trim((string) $data['title']) : null;
        $title = $submittedTitle ?: self::titleFromPath($path);
        $submittedSlug = MediaImageMetadata::normalizeSlug($data['slug'] ?? null);
        $slug = $submittedSlug ?: self::uniqueSlug(self::slugFromPath($path), $path);

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

        return [
            'title' => $metadata?->title ?? (filled($path) ? self::titleFromPath($path) : null),
            'slug' => $metadata?->slug ?? (filled($path) ? self::uniqueSlug(self::slugFromPath($path), $path) : null),
            'tags' => $metadata?->tags ?? [],
        ];
    }

    private static function firstUploadedImagePath(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = collect($path)->first();
        }

        if ($path instanceof TemporaryUploadedFile) {
            return null;
        }

        return filled($path) ? (string) $path : null;
    }

    private static function hasTemporaryUpload(mixed $state): bool
    {
        return self::firstTemporaryUploadedFile($state) !== null;
    }

    private static function firstTemporaryUploadedFile(mixed $state): ?TemporaryUploadedFile
    {
        if (is_array($state)) {
            $state = collect($state)->first();
        }

        return $state instanceof TemporaryUploadedFile ? $state : null;
    }

    private static function titleFromUploadedFile(TemporaryUploadedFile $file): ?string
    {
        $title = str(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            ->replaceMatches('/[\s_.-]+/', ' ')
            ->trim()
            ->headline()
            ->toString();

        return filled($title) ? $title : null;
    }

    private static function slugFromUploadedFile(TemporaryUploadedFile $file): ?string
    {
        return MediaImageMetadata::normalizeSlug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
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

    private static function currentUserId(): ?int
    {
        $user = Filament::auth()->user();

        return $user ? (int) $user->getKey() : null;
    }
}

<?php

namespace App\Filament\Admin\Forms;

use App\Filament\Admin\Forms\Components\ImageGalleryPicker;
use App\Models\MediaImageMetadata;
use App\Support\MediaLibrary as MediaLibrarySupport;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageUpload
{
    public static function make(string $name, string $directory, ?string $label = null): FileUpload
    {
        $upload = FileUpload::make($name)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => self::storedImageFilename($file))
            ->saveUploadedFileUsing(function (FileUpload $component, TemporaryUploadedFile $file): ?string {
                $path = $component->saveUploadedFile($file);

                if (filled($path)) {
                    self::saveDefaultMetadataForState($path);
                }

                return $path;
            });

        if ($label) {
            $upload->label($label);
        }

        return self::configure($upload);
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
                    ->modalHeading('Choose an existing image')
                    ->modalSubmitActionLabel('Use selected image')
                    ->modalWidth(Width::Screen)
                    ->stickyModalHeader()
                    ->stickyModalFooter()
                    ->schema([
                        ImageGalleryPicker::make('existing_image_path')
                            ->label('Images')
                            ->required(),
                    ])
                    ->fillForm(fn (FileUpload $component): array => [
                        'existing_image_path' => $component->getState(),
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

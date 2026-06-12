<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Pages\Concerns\RequiresAdminPageAccess;
use App\Filament\Admin\Resources\FileDocuments\FileDocumentResource;
use App\Filament\Admin\Resources\FileDocuments\Tables\FileDocumentsTable;
use App\Filament\Admin\Support\IconOnlyAction;
use App\Models\FileDocument;
use App\Models\WorkflowNotificationRule;
use App\Support\AdminAccess;
use App\Support\FileLibrary;
use App\Support\MediaLibrary as MediaLibrarySupport;
use App\Support\MediaUsage;
use App\Support\WorkflowNotificationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;

class MediaLibrary extends Page implements HasTable
{
    use InteractsWithTable;
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

    #[Url(as: 'library')]
    public string $libraryTab = 'images';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return AdminAccess::canAccessPage($user, static::class)
            || AdminAccess::canAccessToolOrAssignedRecords($user, AdminAccess::FILE_LIBRARY);
    }

    public function mount(): void
    {
        if (! in_array($this->libraryTab, ['images', 'files'], true)) {
            $this->libraryTab = 'images';
        }

        if ($this->libraryTab === 'images' && ! $this->canAccessImages()) {
            $this->libraryTab = 'files';
        }

        if ($this->libraryTab === 'files' && ! $this->canAccessFiles()) {
            $this->libraryTab = 'images';
        }
    }

    public function updatedSearch(): void
    {
        $this->search = trim($this->search);
    }

    public function setLibraryTab(string $tab): void
    {
        if ($tab === 'images' && $this->canAccessImages()) {
            $this->libraryTab = 'images';
        }

        if ($tab === 'files' && $this->canAccessFiles()) {
            $this->libraryTab = 'files';
        }
    }

    public function canAccessImages(): bool
    {
        return AdminAccess::canAccessToolOrAssignedRecords(Filament::auth()->user(), AdminAccess::MEDIA_LIBRARY);
    }

    public function canAccessFiles(): bool
    {
        return AdminAccess::canAccessToolOrAssignedRecords(Filament::auth()->user(), AdminAccess::FILE_LIBRARY);
    }

    public function table(Table $table): Table
    {
        return FileDocumentsTable::configure($table)
            ->query(FileDocumentResource::getEloquentQuery())
            ->modelLabel(FileDocumentResource::getModelLabel())
            ->pluralModelLabel(FileDocumentResource::getPluralModelLabel())
            ->recordTitleAttribute(FileDocumentResource::getRecordTitleAttribute());
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

    protected function createFileAction(): Action
    {
        return Action::make('createFile')
            ->label('New file')
            ->color('success')
            ->modalHeading('New file')
            ->modalSubmitActionLabel('Create file')
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->maxLength(255)
                    ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set('file_name', FileDocument::makeUniqueFileName($state))),
                TextInput::make('file_name')
                    ->label('Optional filename')
                    ->helperText('Used for the stable public link, like /files/connection-card.')
                    ->prefix('/files/')
                    ->maxLength(255)
                    ->rule('alpha_dash'),
                Select::make('category')
                    ->options(fn (): array => FileDocument::categoryOptions())
                    ->searchable()
                    ->default('Other')
                    ->required(),
                ToggleButtons::make('visibility')
                    ->options([
                        FileDocument::VISIBILITY_PUBLIC => 'Public',
                        FileDocument::VISIBILITY_PRIVATE => 'Private',
                    ])
                    ->colors([
                        FileDocument::VISIBILITY_PUBLIC => 'success',
                        FileDocument::VISIBILITY_PRIVATE => 'warning',
                    ])
                    ->icons([
                        FileDocument::VISIBILITY_PUBLIC => 'heroicon-o-globe-americas',
                        FileDocument::VISIBILITY_PRIVATE => 'heroicon-o-lock-closed',
                    ])
                    ->inline()
                    ->default(FileDocument::VISIBILITY_PUBLIC)
                    ->required(),
                FileUpload::make('pending_upload')
                    ->label('File')
                    ->acceptedFileTypes(FileLibrary::allowedMimeTypes())
                    ->disk(FileLibrary::DISK)
                    ->directory(FileLibrary::DIRECTORY)
                    ->storeFileNamesIn('pending_original_name')
                    ->required()
                    ->downloadable()
                    ->columnSpanFull(),
                TextInput::make('pending_original_name')
                    ->hidden(),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                DateTimePicker::make('expires_at')
                    ->label('Expiration date'),
                TagsInput::make('tags'),
            ])
            ->action(function (array $data): void {
                $uploadPath = FileLibrary::normalizeUploadPath($data['pending_upload'] ?? null);

                if (! $uploadPath) {
                    Notification::make()
                        ->title('File upload is required')
                        ->danger()
                        ->send();

                    return;
                }

                $document = FileDocument::query()->create([
                    'title' => $data['title'] ?? null,
                    'file_name' => FileDocument::makeUniqueFileName($data['file_name'] ?? $data['title'] ?? null),
                    'category' => $data['category'] ?? 'Other',
                    'visibility' => $data['visibility'] ?? FileDocument::VISIBILITY_PUBLIC,
                    'description' => $data['description'] ?? null,
                    'tags' => $data['tags'] ?? [],
                    'expires_at' => $data['expires_at'] ?? null,
                    'uploaded_by_id' => Filament::auth()->id(),
                    'updated_by_id' => Filament::auth()->id(),
                ]);

                FileLibrary::createVersion(
                    $document,
                    $uploadPath,
                    FileLibrary::normalizeOriginalName($data['pending_original_name'] ?? null),
                    Filament::auth()->user(),
                );

                app(WorkflowNotificationService::class)->automaticForRecord(
                    $document->refresh(),
                    WorkflowNotificationRule::TRIGGER_CREATED,
                );

                $this->libraryTab = 'files';
                $this->resetTable();

                Notification::make()
                    ->title('File created')
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

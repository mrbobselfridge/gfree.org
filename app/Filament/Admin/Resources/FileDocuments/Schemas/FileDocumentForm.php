<?php

namespace App\Filament\Admin\Resources\FileDocuments\Schemas;

use App\Filament\Admin\Forms\ImageUpload;
use App\Filament\Admin\Forms\RichEditorDefaults;
use App\Filament\Admin\Resources\Pages\Schemas\PageForm;
use App\Models\FileCategory;
use App\Models\FileDocument;
use App\Support\FileLibrary;
use App\Support\MediaTagOptions;
use App\Support\UploadedFilenameTitle;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FileDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('File Details')
                    ->schema([
                        Select::make('category')
                            ->options(fn (?FileDocument $record): array => FileCategory::options($record?->category))
                            ->searchable()
                            ->preload()
                            ->default(FileCategory::DEFAULT_NAME)
                            ->live()
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Groups this file for filtering and controls the category-specific AI extraction instructions.'
                            )
                            ->hintColor('gray')
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?string $old, ?string $operation, ?FileDocument $record): void {
                                $defaultParentPageId = FileCategory::defaultParentPageIdFor($state);

                                if ($defaultParentPageId !== null) {
                                    $set('parent_page_id', $defaultParentPageId);
                                }

                                if ($operation !== 'create' || ! self::shouldUpdateGeneratedFileName($get, $old, $get('title'), $record)) {
                                    return;
                                }

                                $set('file_name', FileDocument::makeUniqueFileNameForCategoryTitle($state, $get('title'), $record));
                            })
                            ->required(),
                        ToggleButtons::make('is_published')
                            ->label('File is live')
                            ->boolean()
                            ->inline()
                            ->default(true)
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Controls whether this file URL can load publicly, subject to publish and expiration dates.'
                            )
                            ->hintColor('gray')
                            ->required(),
                        FileUpload::make('pending_upload')
                            ->label('File')
                            ->acceptedFileTypes(FileLibrary::allowedMimeTypes())
                            ->disk(FileLibrary::DISK)
                            ->directory(FileLibrary::DIRECTORY)
                            ->storeFileNamesIn('pending_original_name')
                            ->required(fn (?string $operation): bool => $operation === 'create')
                            ->downloadable()
                            ->visible(fn (?string $operation): bool => $operation === 'create')
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Upload the first file version. Accepted types are limited to the File Library allowed document types.'
                            )
                            ->hintColor('gray')
                            ->afterStateUpdated(function (Set $set, Get $get, mixed $state, ?string $operation, ?FileDocument $record): void {
                                if ($operation !== 'create' || blank($state)) {
                                    return;
                                }

                                $title = self::titleFromUploadedFilename($get('pending_original_name'), $state);
                                $fileName = self::fileNameFromUploadedFilename($get('pending_original_name'), $state);
                                $datedFileName = self::datedFileNameForUpload(
                                    $get('file_name'),
                                    $fileName,
                                    self::filePathDateSuffixFromUploadedFilename($get('pending_original_name'), $state),
                                );

                                if (blank($get('publish_at'))) {
                                    $set('publish_at', self::publishDateFromUploadedFilename($get('pending_original_name'), $state));
                                }

                                if (filled($title) && self::shouldUseUploadedFilenameValue($get('title'), null)) {
                                    $set('title', $title);
                                }

                                if (filled($datedFileName)) {
                                    $set('file_name', FileDocument::makeUniqueFileName($datedFileName, $record));
                                }

                                self::mergeAutoTagsIntoForm($set, $get, $title ?: $get('title'));
                            })
                            ->columnSpanFull(),
                        TextInput::make('pending_original_name')
                            ->hidden(),
                        FileUpload::make('current_file')
                            ->label('Current file')
                            ->disk(FileLibrary::DISK)
                            ->afterStateHydrated(fn (FileUpload $component, ?FileDocument $record): mixed => $component->state($record?->currentVersion?->path))
                            ->getUploadedFileUsing(fn (?FileDocument $record): ?array => $record?->currentVersion ? [
                                'name' => $record->currentVersion->original_name,
                                'size' => $record->currentVersion->size,
                                'type' => $record->currentVersion->mime_type,
                                'url' => $record->downloadUrl(),
                            ] : null)
                            ->getDownloadableFileUrlUsing(fn (?FileDocument $record): ?string => $record?->downloadUrl())
                            ->getOpenableFileUrlUsing(fn (?FileDocument $record): ?string => $record?->downloadUrl())
                            ->downloadable()
                            ->openable()
                            ->maxFiles(1)
                            ->deletable(false)
                            ->dehydrated(false)
                            ->visible(fn (?string $operation, ?FileDocument $record): bool => $operation === 'edit' && $record?->currentVersion !== null)
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Shows the currently active file version. Use Replace file to upload a new version.'
                            )
                            ->hintColor('gray')
                            ->columnSpanFull(),
                        TextInput::make('title')
                            ->label('File title')
                            ->required()
                            ->live(onBlur: true)
                            ->maxLength(255)
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Admin and public title for this file. New files use this with Category to build the first path.'
                            )
                            ->hintColor('gray')
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?string $old, ?string $operation, ?FileDocument $record): void {
                                self::mergeAutoTagsIntoForm($set, $get, $state);

                                if ($operation !== 'create' || ! self::shouldUpdateGeneratedFileName($get, $get('category'), $old, $record)) {
                                    return;
                                }

                                $set('file_name', FileDocument::makeUniqueFileNameForCategoryTitle($get('category'), $state, $record));
                            }),
                        ToggleButtons::make('visibility')
                            ->label('Visibility')
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
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Public files can be viewed by anyone. Private published files require a user or admin login.'
                            )
                            ->hintColor('gray')
                            ->required(),
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
                            ->nestedRecursiveRules(['max:80'])
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional tags for organizing files. Uploading a file or editing the title can add matching tags automatically.'
                            )
                            ->hintColor('gray'),
/**                        Textarea::make('description')
                            ->rows(1)
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional intro text on cards of parent page.'
                            )
                            ->hintColor('gray'),
**/
                        TextInput::make('file_name')
                            ->label('File path')
                            ->prefix('/files/')
                            ->live(onBlur: true)
                            ->maxLength(255)
                            ->rule('alpha_dash')
                            ->unique(ignoreRecord: true)
                            ->suffixAction(
                                Action::make('rebuildFileName')
                                    ->label('Generate path')
                                    ->tooltip('Generate path')
                                    ->icon(Heroicon::OutlinedArrowPath)
                                    ->color('gray')
                                    ->action(fn (Get $get, Set $set, ?FileDocument $record): mixed => $set(
                                        'file_name',
                                        FileDocument::makeUniqueFileNameForCategoryTitle($get('category'), $get('title'), $record),
                                    )),
                            )
                            ->dehydrateStateUsing(fn (?string $state, Get $get, ?FileDocument $record): string => filled($state)
                                ? Str::slug($state)
                                : FileDocument::makeUniqueFileNameForCategoryTitle($get('category'), $get('title'), $record))
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Stable URL path ending under /files/. Defaults to category-title and can be generated with the refresh icon.'
                            )
                            ->hintColor('gray'),
                        TextInput::make('sort_order')
                            ->label('Sort order')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Lower numbers appear earlier when a parent page Child Cards block sorts by Sort order.'
                            )
                            ->hintColor('gray'),
                        Select::make('parent_page_id')
                            ->label('Parent page')
                            ->options(fn (): array => PageForm::parentPageOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->exists('pages', 'id')
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional. Lists this file under a parent page such as Resources, Forms, or Bulletins.'
                            )
                            ->hintColor('gray'),
                        FileUpload::make('replacement_upload')
                            ->label('Replace file')
                            ->helperText('Optional. Uploading a replacement creates a new version and keeps older versions available below.')
                            ->acceptedFileTypes(FileLibrary::allowedMimeTypes())
                            ->disk(FileLibrary::DISK)
                            ->directory(FileLibrary::DIRECTORY)
                            ->storeFileNamesIn('replacement_original_name')
                            ->downloadable()
                            ->visible(fn (?string $operation): bool => $operation === 'edit')
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional. Uploading here creates a new current version and preserves older versions below.'
                            )
                            ->hintColor('gray')
                            ->columnSpanFull(),
                        TextInput::make('replacement_original_name')
                            ->hidden(),
                        DateTimePicker::make('publish_at')
                            ->label('Publish at')
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional. Leave empty to allow the file to be available immediately once File is live is enabled.'
                            )
                            ->hintColor('gray'),
                        DateTimePicker::make('expires_at')
                            ->label('Expires at')
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional. Use for temporary files that should stop loading after a certain date.'
                            )
                            ->hintColor('gray'),
                        Placeholder::make('created_at')
                            ->label('Created date')
                            ->content(fn (?FileDocument $record): string => $record?->created_at?->toDayDateTimeString() ?? 'Set when the file is created')
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Set automatically when the file record is first created.'
                            )
                            ->hintColor('gray'),
                        Placeholder::make('updated_at')
                            ->label('Updated date')
                            ->content(fn (?FileDocument $record): string => $record?->updated_at?->toDayDateTimeString() ?? 'Set when the file is saved')
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Set automatically each time the file record is saved.'
                            )
                            ->hintColor('gray'),

                        RichEditorDefaults::configure(RichEditor::make('content'))
                            ->label('Optional file content')
                            ->helperText('Optional formatted notes. This can hold extracted or AI-assisted content later.')
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional rich text shown with the file record. AI extraction can place reviewed content here.'
                            )
                            ->hintColor('gray'),
                        ...ImageUpload::make(
                            'card_image_path',
                            'file-documents/card-images',
                            'Card image',
                            fn (ViewField $upload): ViewField => $upload
                                ->hintIcon(
                                    Heroicon::OutlinedInformationCircle,
                                    'Optional image used when this file appears in cards or listing areas. If empty, the category default image is used before the standard file image.'
                                )
                                ->hintColor('gray'),
                        ),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    private static function shouldUpdateGeneratedFileName(Get $get, ?string $previousCategory, ?string $previousTitle, ?FileDocument $record): bool
    {
        $current = trim((string) $get('file_name'));

        if (blank($current)) {
            return true;
        }

        return $current === FileDocument::makeUniqueFileNameForCategoryTitle($previousCategory, $previousTitle, $record);
    }

    private static function shouldUseUploadedFilenameValue(mixed $currentValue, mixed $existingValue): bool
    {
        $currentValue = filled($currentValue) ? trim((string) $currentValue) : null;
        $existingValue = filled($existingValue) ? trim((string) $existingValue) : null;

        return $currentValue === null || ($existingValue !== null && $currentValue === $existingValue);
    }

    private static function mergeAutoTagsIntoForm(Set $set, Get $get, ?string $title): void
    {
        $set('tags', FileDocument::mergeAutoTags($get('tags') ?? [], $title));
    }

    private static function titleFromUploadedFilename(mixed $originalName, mixed $path): ?string
    {
        return UploadedFilenameTitle::fromStem(self::uploadedFilenameStem($originalName, $path));
    }

    private static function publishDateFromUploadedFilename(mixed $originalName, mixed $path): string
    {
        return self::dateFromUploadedFilename($originalName, $path)->format('Y-m-d H:i:s');
    }

    private static function filePathDateSuffixFromUploadedFilename(mixed $originalName, mixed $path): string
    {
        return self::dateFromUploadedFilename($originalName, $path)->format('Ymd');
    }

    private static function dateFromUploadedFilename(mixed $originalName, mixed $path): CarbonInterface
    {
        $date = UploadedFilenameTitle::dateFromStem(self::uploadedFilenameStem($originalName, $path));

        return $date ? $date->startOfDay() : now()->startOfDay();
    }

    private static function fileNameFromUploadedFilename(mixed $originalName, mixed $path): ?string
    {
        $stem = self::uploadedFilenameStem($originalName, $path);
        $fileName = Str::slug(UploadedFilenameTitle::textFromStemWithoutDate($stem) ?? $stem);

        return filled($fileName) ? $fileName : null;
    }

    private static function datedFileNameForUpload(mixed $currentFileName, ?string $uploadedFileName, string $dateSuffix): string
    {
        $base = filled($currentFileName) ? (string) $currentFileName : $uploadedFileName;
        $base = Str::slug((string) $base);
        $base = preg_replace('/-\d{8}$/', '', $base) ?: '';

        return filled($base) ? "{$base}-{$dateSuffix}" : $dateSuffix;
    }

    private static function uploadedFilenameStem(mixed $originalName, mixed $path): string
    {
        $source = is_array($originalName) ? collect($originalName)->first() : $originalName;

        if (blank($source)) {
            $source = self::originalNameFromUploadState($path);
        }

        $source = filled($source) ? (string) $source : 'file';

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
}

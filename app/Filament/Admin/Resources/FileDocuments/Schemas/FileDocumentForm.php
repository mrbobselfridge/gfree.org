<?php

namespace App\Filament\Admin\Resources\FileDocuments\Schemas;

use App\Filament\Admin\Forms\ImageUpload;
use App\Filament\Admin\Forms\RichEditorDefaults;
use App\Filament\Admin\Resources\Pages\Schemas\PageForm;
use App\Models\FileCategory;
use App\Models\FileDocument;
use App\Support\FileLibrary;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

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
                                if ($operation !== 'create' || ! self::shouldUpdateGeneratedFileName($get, $old, $get('title'), $record)) {
                                    return;
                                }

                                $set('file_name', FileDocument::makeUniqueFileNameForCategoryTitle($state, $get('title'), $record));
                            })
                            ->required(),
                        ToggleButtons::make('is_published')
                            ->label('Make File Live')
                            ->boolean()
                            ->inline()
                            ->default(true)
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Controls whether this file URL can load publicly, subject to publish and expiration dates.'
                            )
                            ->hintColor('gray')
                            ->required(),
                        TextInput::make('title')
                            ->required()
                            ->live(onBlur: true)
                            ->maxLength(255)
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Admin and public title for this file. New files use this with Category to build the first slug.'
                            )
                            ->hintColor('gray')
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?string $old, ?string $operation, ?FileDocument $record): void {
                                if ($operation !== 'create' || ! self::shouldUpdateGeneratedFileName($get, $get('category'), $old, $record)) {
                                    return;
                                }

                                $set('file_name', FileDocument::makeUniqueFileNameForCategoryTitle($get('category'), $state, $record));
                            }),
                        ToggleButtons::make('visibility')
                            ->label('Public or private')
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
                        TextInput::make('file_name')
                            ->label('Slug')
                            ->prefix('/files/')
                            ->live(onBlur: true)
                            ->maxLength(255)
                            ->rule('alpha_dash')
                            ->unique(ignoreRecord: true)
                            ->suffixAction(
                                Action::make('rebuildFileName')
                                    ->label('Rebuild slug')
                                    ->tooltip('Rebuild slug')
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
                                'Stable URL ending under /files/. Defaults to category-title and can be rebuilt with the refresh icon.'
                            )
                            ->hintColor('gray'),
                        Select::make('parent_page_id')
                            ->label('Parent Page - optional')
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
                        ImageUpload::make('card_image_path', 'file-documents/card-images', 'Card image')
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional image used when this file appears in cards or listing areas. If empty, the default file image is used.'
                            )
                            ->hintColor('gray'),
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
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Document Notes')
                    ->schema([
                        RichEditorDefaults::configure(RichEditor::make('content'))
                            ->label('Optional content')
                            ->helperText('Optional formatted notes. This can hold extracted or AI-assisted content later.')
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional rich text shown with the file record. AI extraction can place reviewed content here.'
                            )
                            ->hintColor('gray')
                            ->columnSpanFull(),
                        DateTimePicker::make('publish_at')
                            ->label('Publish date')
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional. Leave empty to allow the file to be available immediately once Make File Live is Yes.'
                            )
                            ->hintColor('gray'),
                        DateTimePicker::make('expires_at')
                            ->label('Expiration date')
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional. Use for temporary files that should stop loading after a certain date.'
                            )
                            ->hintColor('gray'),
                        Placeholder::make('created_at')
                            ->label('Created Date')
                            ->content(fn (?FileDocument $record): string => $record?->created_at?->toDayDateTimeString() ?? 'Set when the file is created')
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Set automatically when the file record is first created.'
                            )
                            ->hintColor('gray'),
                        Placeholder::make('updated_at')
                            ->label('Updated Date')
                            ->content(fn (?FileDocument $record): string => $record?->updated_at?->toDayDateTimeString() ?? 'Set when the file is saved')
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Set automatically each time the file record is saved.'
                            )
                            ->hintColor('gray'),
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
}

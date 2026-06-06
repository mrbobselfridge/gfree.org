<?php

namespace App\Filament\Admin\Resources\FileDocuments\Schemas;

use App\Filament\Admin\Forms\RichEditorDefaults;
use App\Models\FileDocument;
use App\Support\FileLibrary;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FileDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('File Details')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->live(onBlur: true)
                            ->maxLength(255)
                            ->afterStateUpdated(fn (Set $set, ?string $state, ?string $operation): mixed => $operation === 'create'
                                ? $set('file_name', FileDocument::makeUniqueFileName($state))
                                : null),
                        TextInput::make('file_name')
                            ->label('Optional filename')
                            ->helperText('Used for the stable public link, like /files/connection-card. If blank, one is generated from the title.')
                            ->prefix('/files/')
                            ->live(onBlur: true)
                            ->maxLength(255)
                            ->rule('alpha_dash')
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (?string $state, Get $get, ?FileDocument $record): string => filled($state)
                                ? Str::slug($state)
                                : FileDocument::makeUniqueFileName($get('title'), $record)),
                        Select::make('category')
                            ->options(fn (): array => FileDocument::categoryOptions())
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('category')
                                    ->label('Category')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(fn (array $data): string => trim((string) $data['category']))
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
                            ->required(fn (?string $operation): bool => $operation === 'create')
                            ->downloadable()
                            ->columnSpanFull(),
                        TextInput::make('pending_original_name')
                            ->hidden(),
                        FileUpload::make('replacement_upload')
                            ->label('Replace file')
                            ->helperText('Optional. Uploading a replacement creates a new version and keeps older versions available below.')
                            ->acceptedFileTypes(FileLibrary::allowedMimeTypes())
                            ->disk(FileLibrary::DISK)
                            ->directory(FileLibrary::DIRECTORY)
                            ->storeFileNamesIn('replacement_original_name')
                            ->downloadable()
                            ->visible(fn (?string $operation): bool => $operation === 'edit')
                            ->columnSpanFull(),
                        TextInput::make('replacement_original_name')
                            ->hidden(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Document Notes')
                    ->schema([
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        RichEditorDefaults::configure(RichEditor::make('content'))
                            ->label('Optional content')
                            ->helperText('Optional formatted notes. This can hold extracted or AI-assisted content later.')
                            ->columnSpanFull(),
                        DateTimePicker::make('expires_at')
                            ->label('Expiration date'),
                        TagsInput::make('tags'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}

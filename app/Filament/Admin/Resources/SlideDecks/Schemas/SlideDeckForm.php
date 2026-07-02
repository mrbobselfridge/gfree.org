<?php

namespace App\Filament\Admin\Resources\SlideDecks\Schemas;

use App\Filament\Admin\Forms\InternalNotes;
use App\Models\SlideDeck;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SlideDeckForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                TextInput::make('name')
                    ->label('Deck name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2)
                    ->hintIcon(Heroicon::OutlinedInformationCircle, 'Internal name for this imported announcement deck.')
                    ->hintColor('gray'),
                TextInput::make('status')
                    ->formatStateUsing(fn (?string $state): string => SlideDeck::statuses()[$state] ?? 'Pending')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (?string $operation): bool => $operation === 'edit'),
                FileUpload::make('deck_upload')
                    ->label('PowerPoint deck')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.presentationml.presentation'])
                    ->disk(SlideDeck::DISK)
                    ->directory('slide-decks/uploads')
                    ->storeFileNamesIn('original_filename')
                    ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => self::storedUploadName($file))
                    ->required(fn (?string $operation): bool => $operation === 'create')
                    ->visible(fn (?string $operation): bool => $operation === 'create')
                    ->helperText('Upload a .pptx file. Processing runs in the queue after the record is created.')
                    ->columnSpan(2),
                Hidden::make('original_filename'),
                Placeholder::make('original_filename_display')
                    ->label('Original filename')
                    ->content(fn (?SlideDeck $record): string => $record?->original_filename ?? 'Set after upload')
                    ->visible(fn (?string $operation): bool => $operation === 'edit'),
                Placeholder::make('slides_progress')
                    ->label('Slides')
                    ->content(fn (?SlideDeck $record): string => $record
                        ? "{$record->processed_slides} processed of {$record->total_slides}"
                        : 'Processed after upload')
                    ->visible(fn (?string $operation): bool => $operation === 'edit'),
                Placeholder::make('file_library_record')
                    ->label('File Library record')
                    ->content(fn (?SlideDeck $record): string => $record?->fileDocument?->title ?? 'Created after upload')
                    ->visible(fn (?string $operation): bool => $operation === 'edit'),
                Placeholder::make('error_message')
                    ->label('Latest error')
                    ->content(fn (?SlideDeck $record): string => $record?->error_message ?: 'None')
                    ->visible(fn (?SlideDeck $record): bool => filled($record?->error_message))
                    ->columnSpanFull(),
                InternalNotes::field(),
            ]);
    }

    private static function storedUploadName(TemporaryUploadedFile $file): string
    {
        $stem = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'slide-deck';

        return Str::ulid().'-'.$stem.'.pptx';
    }
}

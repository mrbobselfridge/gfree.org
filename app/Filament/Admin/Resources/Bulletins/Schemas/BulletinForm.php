<?php

namespace App\Filament\Admin\Resources\Bulletins\Schemas;

use App\Filament\Admin\Forms\RichEditorDefaults;
use App\Models\Bulletin;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Throwable;

class BulletinForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Bulletin Details')
                    ->schema([
                        DatePicker::make('bulletin_date')
                            ->label('Bulletin date')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old): void {
                                $title = $get('title');
                                $previousDefaultTitle = self::titleFromDate($old);

                                if (filled($title) && $title !== $previousDefaultTitle) {
                                    return;
                                }

                                $set('title', self::titleFromDate($state));
                            }),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        ToggleButtons::make('is_published')
                            ->label('Make Bulletin Live')
                            ->boolean()
                            ->inline()
                            ->default(false)
                            ->required(),
                        FileUpload::make('pdf_path')
                            ->label('Bulletin PDF')
                            ->acceptedFileTypes(['application/pdf'])
                            ->disk('public')
                            ->directory('bulletins/pdfs')
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('PDF Extraction')
                    ->description('Upload a PDF, write what should be extracted, save the bulletin, then use Extract PDF on the edit page.')
                    ->visible(fn (?Bulletin $record): bool => $record?->exists ?? false)
                    ->schema([
                        Textarea::make('extraction_prompt')
                            ->label('Extraction instructions')
                            ->default(self::defaultExtractionPrompt())
                            ->rows(5)
                            ->columnSpanFull(),
                        RichEditorDefaults::configure(RichEditor::make('extracted_html'))
                            ->label('Extracted formatted HTML')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function defaultExtractionPrompt(): string
    {
        return 'Extract the important public bulletin content for the church website. Preserve headings, dates, event details, announcements, contact information, and links when available. Return clean formatted HTML with headings, paragraphs, and bullet lists where helpful. Anywhere it notes Connection Card - please link that to /card on this site in a new window. ';
    }

    private static function titleFromDate(?string $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        try {
            return 'Bulletin '.Carbon::parse($date)->format('F j, Y');
        } catch (Throwable) {
            return null;
        }
    }
}

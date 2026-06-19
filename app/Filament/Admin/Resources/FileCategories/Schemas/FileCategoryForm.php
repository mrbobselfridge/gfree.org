<?php

namespace App\Filament\Admin\Resources\FileCategories\Schemas;

use App\Filament\Admin\Forms\ImageUpload;
use App\Filament\Admin\Resources\Pages\Schemas\PageForm;
use App\Support\FileCategoryExtractionInstructions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class FileCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('default_parent_page_id')
                    ->label('Default Parent Page')
                    ->options(fn (): array => PageForm::parentPageOptions())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->exists('pages', 'id')
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional. When a file uses this category, this page is suggested as the file parent and can still be changed.'
                    )
                    ->hintColor('gray')
                    ->columnSpanFull(),
                Textarea::make('extraction_instructions')
                    ->label('Extraction Instructions')
                    ->helperText('Used by Extract File Content AI for files in this category.')
                    ->default(fn (): string => FileCategoryExtractionInstructions::DEFAULT)
                    ->rows(6),
                ...ImageUpload::make(
                    'default_card_image_path',
                    'file-categories/default-card-images',
                    'Default Card Image',
                    fn (ViewField $upload): ViewField => $upload
                        ->hintIcon(
                            Heroicon::OutlinedInformationCircle,
                            'Optional fallback image used by files in this category when the file does not have its own card image.'
                        )
                        ->hintColor('gray'),
                ),
            ]);
    }
}

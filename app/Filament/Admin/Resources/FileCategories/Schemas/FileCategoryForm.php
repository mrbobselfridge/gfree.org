<?php

namespace App\Filament\Admin\Resources\FileCategories\Schemas;

use App\Support\FileCategoryExtractionInstructions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

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
                Textarea::make('extraction_instructions')
                    ->label('Extraction Instructions')
                    ->helperText('Used by Extract File Content AI for files in this category.')
                    ->default(fn (): string => FileCategoryExtractionInstructions::DEFAULT)
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}

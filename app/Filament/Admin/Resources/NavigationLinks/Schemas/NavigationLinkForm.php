<?php

namespace App\Filament\Admin\Resources\NavigationLinks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class NavigationLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('Parent link')
                    ->relationship('parent', 'label')
                    ->searchable()
                    ->preload(),
                TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                TextInput::make('url')
                    ->required()
                    ->maxLength(255),
                TextInput::make('location')
                    ->required()
                    ->default('header')
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('opens_in_new_tab')
                    ->label('Open in new tab')
                    ->default(false)
                    ->required(),
                Toggle::make('is_published')
                    ->default(false)
                    ->required(),
            ]);
    }
}

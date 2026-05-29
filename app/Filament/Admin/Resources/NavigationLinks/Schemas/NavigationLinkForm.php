<?php

namespace App\Filament\Admin\Resources\NavigationLinks\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
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
                ToggleButtons::make('is_published')
                    ->label('Make Link Live')
                    ->boolean()
                    ->inline()
                    ->default(false)
                    ->required(),
                TextInput::make('label')
                    ->label('Link Text')
                    ->required()
                    ->maxLength(255),
                ToggleButtons::make('opens_in_new_tab')
                    ->label('Open in new tab')
                    ->boolean()
                    ->inline()
                    ->default(false)
                    ->required(),
                TextInput::make('url')
                    ->required()
                    ->maxLength(255),
                Hidden::make('location')
                    ->default('header')
                    ->dehydrateStateUsing(fn (): string => 'header'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('publish_at')
                    ->label('Publish at'),
                DateTimePicker::make('expires_at')
                    ->label('Expires at')
                    ->afterOrEqual(fn (Get $get): ?string => $get('publish_at')),
            ]);
    }
}

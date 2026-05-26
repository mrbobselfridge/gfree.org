<?php

namespace App\Filament\Admin\Resources\HomepageBanners\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class HomepageBannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                ToggleButtons::make('is_published')
                    ->label('Make Announcement Live')
                    ->boolean()
                    ->inline()
                    ->default(false)
                    ->required(),
                TextInput::make('eyebrow')
                    ->label('Small label')
                    ->maxLength(255),
                Textarea::make('subtitle')
                    ->rows(1),
                DateTimePicker::make('starts_at')
                    ->label('Starts at'),
                DateTimePicker::make('ends_at')
                    ->label('Ends at')
                    ->afterOrEqual(fn (Get $get): ?string => $get('starts_at')),
                TextInput::make('button_label')
                    ->label('Primary button label')
                    ->maxLength(255),
                TextInput::make('button_url')
                    ->label('Primary button URL')
                    ->maxLength(255),
                TextInput::make('secondary_button_label')
                    ->maxLength(255),
                TextInput::make('secondary_button_url')
                    ->maxLength(255),
                FileUpload::make('image_path')
                    ->image()
                    ->disk('public')
                    ->directory('homepage-banners')
                    ->columnSpanFull(),

            ]);
    }
}

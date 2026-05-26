<?php

namespace App\Filament\Admin\Resources\HomepageBanners\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
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
                TextInput::make('eyebrow')
                    ->label('Small label')
                    ->helperText('Optional. If empty, the homepage uses the default label.')
                    ->maxLength(255),
                Textarea::make('subtitle')
                    ->rows(3)
                    ->columnSpanFull(),
                FileUpload::make('image_path')
                    ->image()
                    ->disk('public')
                    ->directory('homepage-banners'),
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
                DateTimePicker::make('starts_at')
                    ->label('Starts at'),
                DateTimePicker::make('ends_at')
                    ->label('Ends at')
                    ->afterOrEqual(fn (Get $get): ?string => $get('starts_at')),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_published')
                    ->default(false)
                    ->required(),
            ]);
    }
}

<?php

namespace App\Filament\Admin\Resources\Ministries\Schemas;

use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MinistryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->maxLength(255)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Textarea::make('short_summary')
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->rows(10)
                    ->columnSpanFull(),
                FileUpload::make('hero_image_path')
                    ->image()
                    ->disk('public')
                    ->directory('ministries/hero-images'),
                FileUpload::make('card_image_path')
                    ->image()
                    ->disk('public')
                    ->directory('ministries/card-images'),
                TextInput::make('category')
                    ->maxLength(255),
                TextInput::make('meeting_time')
                    ->maxLength(255),
                TextInput::make('location')
                    ->maxLength(255),
                TextInput::make('leader_name')
                    ->maxLength(255),
                TextInput::make('leader_email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('one_church_url')
                    ->label('One Church URL')
                    ->url()
                    ->maxLength(255),
                Textarea::make('embed_code')
                    ->label('Embed code')
                    ->rows(5)
                    ->columnSpanFull(),
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

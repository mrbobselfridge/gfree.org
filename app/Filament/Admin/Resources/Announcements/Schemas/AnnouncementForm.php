<?php

namespace App\Filament\Admin\Resources\Announcements\Schemas;

use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->maxLength(255)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Textarea::make('summary')
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('body')
                    ->rows(8)
                    ->columnSpanFull(),
                FileUpload::make('image_path')
                    ->image()
                    ->disk('public')
                    ->directory('announcements'),
                TextInput::make('cta_label')
                    ->label('Button label')
                    ->maxLength(255),
                TextInput::make('cta_url')
                    ->label('Button URL')
                    ->maxLength(255),
                DateTimePicker::make('publish_at'),
                DateTimePicker::make('expires_at'),
                Toggle::make('is_featured')
                    ->label('Featured on homepage')
                    ->default(false)
                    ->required(),
                Toggle::make('is_published')
                    ->default(false)
                    ->required(),
            ]);
    }
}

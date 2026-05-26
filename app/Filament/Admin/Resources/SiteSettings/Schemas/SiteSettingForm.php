<?php

namespace App\Filament\Admin\Resources\SiteSettings\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SiteSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('church_name')
                    ->required()
                    ->default('gFree Church')
                    ->maxLength(255),
                TextInput::make('tagline')
                    ->maxLength(255),
                Textarea::make('sunday_service_times')
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('address')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('phone')
                    ->maxLength(255)
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->maxLength(255),
                Textarea::make('office_hours')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('livestream_url')
                    ->url()
                    ->maxLength(255),
                TextInput::make('giving_url')
                    ->url()
                    ->maxLength(255),
                TextInput::make('one_church_url')
                    ->label('One Church URL')
                    ->url()
                    ->maxLength(255),
                TextInput::make('facebook_url')
                    ->url()
                    ->maxLength(255),
                TextInput::make('instagram_url')
                    ->url()
                    ->maxLength(255),
                TextInput::make('youtube_url')
                    ->url()
                    ->maxLength(255),
                TextInput::make('announcements_small_label')
                    ->label('Announcements small label')
                    ->maxLength(255),
                TextInput::make('announcements_title')
                    ->label('Announcements title')
                    ->maxLength(255),
                Textarea::make('announcements_subtitle')
                    ->label('Announcements subtitle')
                    ->rows(3)
                    ->columnSpanFull(),
                FileUpload::make('announcements_image_path')
                    ->label('Announcements image')
                    ->image()
                    ->disk('public')
                    ->directory('site-settings/announcements'),
                TextInput::make('leadership_small_label')
                    ->label('Leadership small label')
                    ->maxLength(255),
                TextInput::make('leadership_title')
                    ->label('Leadership title')
                    ->maxLength(255),
                Textarea::make('leadership_subtitle')
                    ->label('Leadership subtitle')
                    ->rows(3)
                    ->columnSpanFull(),
                FileUpload::make('leadership_image_path')
                    ->label('Leadership image')
                    ->image()
                    ->disk('public')
                    ->directory('site-settings/leadership'),
                TextInput::make('ministry_small_label')
                    ->label('Ministry small label')
                    ->maxLength(255),
                TextInput::make('ministry_title')
                    ->label('Ministry title')
                    ->maxLength(255),
                Textarea::make('ministry_subtitle')
                    ->label('Ministry subtitle')
                    ->rows(3)
                    ->columnSpanFull(),
                FileUpload::make('ministry_image_path')
                    ->label('Ministry image')
                    ->image()
                    ->disk('public')
                    ->directory('site-settings/ministry'),
            ]);
    }
}

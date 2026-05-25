<?php

namespace App\Filament\Admin\Resources\SiteSettings\Schemas;

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
            ]);
    }
}

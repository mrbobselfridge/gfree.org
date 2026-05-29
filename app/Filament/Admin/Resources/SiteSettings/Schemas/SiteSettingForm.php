<?php

namespace App\Filament\Admin\Resources\SiteSettings\Schemas;

use App\Filament\Admin\Forms\RichEditorDefaults;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
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
                TextInput::make('phone')
                    ->maxLength(255)
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->maxLength(255),
                TextInput::make('tagline')
                    ->maxLength(255),
                RichEditorDefaults::configure(RichEditor::make('sunday_service_times')),
                RichEditorDefaults::configure(RichEditor::make('address')),
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
                RichEditorDefaults::configure(RichEditor::make('office_hours'))
                    ->columnSpanFull(),
                TextInput::make('announcements_small_label')
                    ->label('Announcements small label')
                    ->maxLength(255),
                TextInput::make('announcements_title')
                    ->label('Announcements title')
                    ->maxLength(255),
                RichEditorDefaults::configure(RichEditor::make('announcements_subtitle'))
                    ->label('Announcements subtitle')
                    ->columnSpanFull(),
                FileUpload::make('announcements_image_path')
                    ->label('Announcements Image')
                    ->image()
                    ->disk('public')
                    ->directory('site-settings/announcements')
                    ->columnSpanFull(),
                TextInput::make('leadership_small_label')
                    ->label('Leadership small label')
                    ->maxLength(255),
                TextInput::make('leadership_title')
                    ->label('Leadership title')
                    ->maxLength(255),
                RichEditorDefaults::configure(RichEditor::make('leadership_subtitle'))
                    ->label('Leadership subtitle')
                    ->columnSpanFull(),
                FileUpload::make('leadership_image_path')
                    ->label('Leadership Image')
                    ->image()
                    ->disk('public')
                    ->directory('site-settings/leadership')
                    ->columnSpanFull(2),
                TextInput::make('ministry_small_label')
                    ->label('Ministry small label')
                    ->maxLength(255),
                TextInput::make('ministry_title')
                    ->label('Ministry title')
                    ->maxLength(255),
                RichEditorDefaults::configure(RichEditor::make('ministry_subtitle'))
                    ->label('Ministry subtitle')
                    ->columnSpanFull(),
                FileUpload::make('ministry_image_path')
                    ->label('Ministry image')
                    ->image()
                    ->disk('public')
                    ->directory('site-settings/ministry')
                    ->columnSpanFull(),
                TextInput::make('sermons_small_label')
                    ->label('Sermons small label')
                    ->maxLength(255),
                TextInput::make('sermons_title')
                    ->label('Sermons title')
                    ->maxLength(255),
                RichEditorDefaults::configure(RichEditor::make('sermons_subtitle'))
                    ->label('Sermons subtitle')
                    ->columnSpanFull(),
                RichEditorDefaults::configure(RichEditor::make('sermons_text'))
                    ->label('Sermons text')
                    ->columnSpanFull(),
                TextInput::make('sermons_youtube_link_label')
                    ->label('View on YouTube text')
                    ->maxLength(255),
                TextInput::make('sermons_youtube_feed_url')
                    ->label('Sermons YouTube feed URL')
                    ->helperText('Optional. Paste a YouTube RSS feed URL to replace the default sermon channel feed.')
                    ->url(),
                TextInput::make('sermons_youtube_channel_url')
                    ->label('Sermons YouTube channel URL')
                    ->helperText('Optional. Used for the View on YouTube link when the feed source changes.')
                    ->url(),
                FileUpload::make('sermons_image_path')
                    ->label('Sermons image')
                    ->image()
                    ->disk('public')
                    ->directory('site-settings/sermons')
                    ->columnSpanFull(),
            ]);
    }
}

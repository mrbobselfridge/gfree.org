<?php

namespace App\Filament\Admin\Resources\SiteSettings\Schemas;

use App\Filament\Admin\Forms\RichEditorDefaults;
use App\Support\YoutubeFeedUrl;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SiteSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Organizational Information')
                    ->schema([
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
                        RichEditorDefaults::configure(RichEditor::make('sunday_service_times'))
                            ->columnSpanFull(),
                        RichEditorDefaults::configure(RichEditor::make('office_hours'))
                            ->columnSpanFull(),
                        RichEditorDefaults::configure(RichEditor::make('address'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Social and Video URLs')
                    ->schema([
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
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Announcements Settings')
                    ->description('Can also be managed in the Announcements area.')
                    ->schema([
                        TextInput::make('announcements_small_label')
                            ->label('Announcements small label')
                            ->maxLength(255),
                        TextInput::make('announcements_title')
                            ->label('Announcements title')
                            ->maxLength(255),
                        RichEditorDefaults::configure(RichEditor::make('announcements_subtitle'))
                            ->label('Announcements subtitle'),
                        FileUpload::make('announcements_image_path')
                            ->label('Announcements Image')
                            ->image()
                            ->disk('public')
                            ->directory('site-settings/announcements'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Ministries Settings')
                    ->description('Can also be managed in the Ministries area.')
                    ->schema([
                        TextInput::make('ministry_small_label')
                            ->label('Ministry small label')
                            ->maxLength(255),
                        TextInput::make('ministry_title')
                            ->label('Ministry title')
                            ->maxLength(255),
                        RichEditorDefaults::configure(RichEditor::make('ministry_subtitle'))
                            ->label('Ministry subtitle'),
                        FileUpload::make('ministry_image_path')
                            ->label('Ministry image')
                            ->image()
                            ->disk('public')
                            ->directory('site-settings/ministry'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Leaders Settings')
                    ->description('Can also be managed in the Leaders area.')
                    ->schema([
                        TextInput::make('leadership_small_label')
                            ->label('Leadership small label')
                            ->maxLength(255),
                        TextInput::make('leadership_title')
                            ->label('Leadership title')
                            ->maxLength(255),
                        RichEditorDefaults::configure(RichEditor::make('leadership_subtitle'))
                            ->label('Leadership subtitle'),
                        FileUpload::make('leadership_image_path')
                            ->label('Leadership Image')
                            ->image()
                            ->disk('public')
                            ->directory('site-settings/leadership'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Sermons Settings')
                    ->description('Can also be managed in the Sermons area.')
                    ->schema([
                        TextInput::make('sermons_small_label')
                            ->label('Sermons small label')
                            ->maxLength(255),
                        TextInput::make('sermons_title')
                            ->label('Sermons title')
                            ->maxLength(255),
                        RichEditorDefaults::configure(RichEditor::make('sermons_subtitle'))
                            ->label('Sermons subtitle'),
                        RichEditorDefaults::configure(RichEditor::make('sermons_text'))
                            ->label('Sermons text'),
                        TextInput::make('sermons_youtube_channel_url')
                            ->label('Sermons YouTube channel URL')
                            ->helperText('Optional. Used for the View on YouTube link when the feed source changes. The RSS feed URL is filled automatically when a channel ID can be found.')
                            ->url()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $feedUrl = YoutubeFeedUrl::fromChannelUrl($state);

                                if ($feedUrl) {
                                    $set('sermons_youtube_feed_url', $feedUrl);
                                }
                            }),
                        TextInput::make('sermons_youtube_feed_url')
                            ->label('Sermons YouTube feed URL')
                            ->helperText('Optional. Paste a YouTube RSS feed URL to replace the default sermon channel feed.')
                            ->url(),
                        TextInput::make('sermons_youtube_link_label')
                            ->label('View on YouTube text')
                            ->maxLength(255),
                        FileUpload::make('sermons_image_path')
                            ->label('Sermons image')
                            ->image()
                            ->disk('public')
                            ->directory('site-settings/sermons'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Bulletins Settings')
                    ->description('Can also be managed in the Bulletins area.')
                    ->schema([
                        TextInput::make('bulletins_small_label')
                            ->label('Bulletins small label')
                            ->maxLength(255),
                        TextInput::make('bulletins_title')
                            ->label('Bulletins title')
                            ->maxLength(255),
                        RichEditorDefaults::configure(RichEditor::make('bulletins_subtitle'))
                            ->label('Bulletins subtitle'),
                        FileUpload::make('bulletins_image_path')
                            ->label('Bulletins Image')
                            ->image()
                            ->disk('public')
                            ->directory('site-settings/bulletins'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}

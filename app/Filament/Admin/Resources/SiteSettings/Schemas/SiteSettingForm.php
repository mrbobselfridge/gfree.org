<?php

namespace App\Filament\Admin\Resources\SiteSettings\Schemas;

use App\Filament\Admin\Forms\ImageUpload;
use App\Filament\Admin\Forms\RichEditorDefaults;
use App\Rules\HttpOrRelativeUrl;
use App\Support\AiBulletinExtractionPrompt;
use App\Support\AiContentPrompt;
use App\Support\OpenAiSiteSettings;
use App\Support\YoutubeFeedUrl;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                            ->default('TwyxtCo Church')
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
                        ImageUpload::make('site_logo_path', 'site-settings/logo', 'Site logo')
                            ->helperText('Used in the public header and footer. Leave blank to use the default logo.'),
                        RichEditorDefaults::configure(RichEditor::make('office_hours')),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('AI Settings')
                    ->schema([
                        TextInput::make('openai_api_key')
                            ->label('OpenAI API key')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->maxLength(1000)
                            ->helperText('Used for AI rewrite tools and bulletin PDF extraction.'),
                        Select::make('openai_bulletin_model')
                            ->label('OpenAI bulletin model')
                            ->options(OpenAiSiteSettings::modelOptions())
                            ->default(OpenAiSiteSettings::DEFAULT_MODEL)
                            ->required()
                            ->native(false),
                        Textarea::make('ai_content_prompt')
                            ->label('AI Content Prompt')
                            ->default(AiContentPrompt::DEFAULT)
                            ->rows(6),
                        Textarea::make('ai_bulletin_extraction_prompt')
                            ->label('AI Bulletin Extraction Prompt')
                            ->default(AiBulletinExtractionPrompt::DEFAULT)
                            ->afterStateHydrated(function (Textarea $component, ?string $state): void {
                                if (blank($state)) {
                                    $component->state(AiBulletinExtractionPrompt::DEFAULT);
                                }
                            })
                            ->helperText('Default extraction instructions for bulletin PDFs. Individual bulletins may still customize their own instructions.')
                            ->rows(6),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Social and Video URLs')
                    ->schema([
                        TextInput::make('livestream_url')
                            ->rules([new HttpOrRelativeUrl])
                            ->maxLength(255),
                        TextInput::make('giving_url')
                            ->rules([new HttpOrRelativeUrl])
                            ->maxLength(255),
                        TextInput::make('one_church_url')
                            ->label('One Church URL')
                            ->rules([new HttpOrRelativeUrl])
                            ->maxLength(255),
                        TextInput::make('facebook_url')
                            ->rules([new HttpOrRelativeUrl])
                            ->maxLength(255),
                        TextInput::make('instagram_url')
                            ->rules([new HttpOrRelativeUrl])
                            ->maxLength(255),
                        TextInput::make('youtube_url')
                            ->rules([new HttpOrRelativeUrl])
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Google Tracking')
                    ->description('Optional. Use Google Tag Manager for the most flexibility. If both are filled in, only Google Tag Manager is rendered to avoid duplicate Analytics page views.')
                    ->schema([
                        TextInput::make('google_tag_manager_id')
                            ->label('Google Tag Manager container ID')
                            ->helperText('Example: GTM-XXXXXXX. This renders the GTM head script and body noscript on public pages.')
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null)
                            ->rule('nullable')
                            ->rule('regex:/^GTM-[A-Z0-9]+$/i')
                            ->validationMessages([
                                'regex' => 'Enter a valid Google Tag Manager ID, such as GTM-XXXXXXX.',
                            ])
                            ->maxLength(255),
                        TextInput::make('google_analytics_measurement_id')
                            ->label('Google Analytics measurement ID')
                            ->helperText('Example: G-XXXXXXXXXX. Used only when no Google Tag Manager ID is set.')
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null)
                            ->rule('nullable')
                            ->rule('regex:/^G-[A-Z0-9]+$/i')
                            ->validationMessages([
                                'regex' => 'Enter a valid Google Analytics measurement ID, such as G-XXXXXXXXXX.',
                            ])
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
                        ImageUpload::make('announcements_image_path', 'site-settings/announcements', 'Announcements Image'),
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
                        ImageUpload::make('ministry_image_path', 'site-settings/ministry', 'Ministry image'),
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
                        ImageUpload::make('leadership_image_path', 'site-settings/leadership', 'Leadership Image'),
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
                            ->rules([new HttpOrRelativeUrl])
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
                            ->rules([new HttpOrRelativeUrl]),
                        TextInput::make('sermons_youtube_link_label')
                            ->label('View on YouTube text')
                            ->maxLength(255),
                        ImageUpload::make('sermons_image_path', 'site-settings/sermons', 'Sermons image'),
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
                        ImageUpload::make('bulletins_image_path', 'site-settings/bulletins', 'Bulletins Image'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}

<?php

namespace App\Filament\Admin\Resources\SiteSettings\Schemas;

use App\Filament\Admin\Forms\ImageUpload;
use App\Filament\Admin\Forms\RichEditorDefaults;
use App\Rules\HttpOrRelativeUrl;
use App\Support\AiContentPrompt;
use App\Support\SiteDesignPalette;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SiteSettingForm
{
    private const SECTION_IDS = [
        'site-settings-organizational-information',
        'site-settings-site-design-elements',
        'site-settings-ai-settings',
        'site-settings-social-and-video-urls',
        'site-settings-google-tracking',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.admin.site-settings-section-controls')
                    ->viewData([
                        'sectionIds' => self::SECTION_IDS,
                    ])
                    ->key('site-settings-section-controls')
                    ->columnSpanFull(),
                self::section('Organizational Information', 'site-settings-organizational-information')
                    ->schema([
                        TextInput::make('church_name')
                            ->label('Site Name')
                            ->required()
                            ->default('TwyxtCo')
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
                        ...ImageUpload::make(
                            'site_logo_path',
                            'site-settings/logo',
                            'Site logo',
                            fn (ViewField $upload): ViewField => $upload
                                ->helperText('Used in the public header and footer. Leave blank to use the default logo.'),
                        ),
                        ...ImageUpload::make(
                            'default_page_header_image_path',
                            'site-settings/page-header-images',
                            'Default page header image',
                            fn (ViewField $upload): ViewField => $upload
                                ->helperText('Used on public pages when Show page header is on but that page has no Header Image selected.'),
                        ),
                        RichEditorDefaults::configure(RichEditor::make('office_hours')),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                self::section('Site Design elements', 'site-settings-site-design-elements')
                    ->schema([
                        Repeater::make('design_background_colors')
                            ->label('Background colors')
                            ->default(SiteDesignPalette::defaultBackgroundColors())
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(80),
                                ColorPicker::make('hex')
                                    ->label('Hex code')
                                    ->hex()
                                    ->placeholder('#17b8ad')
                                    ->required()
                                    ->dehydrateStateUsing(fn (mixed $state): ?string => SiteDesignPalette::normalizeHex($state))
                                    ->rule('regex:/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/')
                                    ->validationMessages([
                                        'regex' => 'Enter a valid hex color, such as #17b8ad.',
                                    ]),
                                Hidden::make('key')
                                    ->dehydrateStateUsing(fn (mixed $state, Get $get): string => SiteDesignPalette::normalizeKey($state) ?? SiteDesignPalette::normalizeKey($get('name')) ?? 'background'),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->addActionLabel('Add background color')
                            ->reorderable()
                            ->dehydrateStateUsing(fn (mixed $state): array => SiteDesignPalette::normalizeBackgroundColors($state) ?: SiteDesignPalette::defaultBackgroundColors())
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'These colors populate the Background color options in page and homepage content blocks.',
                            )
                            ->hintColor('gray'),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
                self::section('AI Settings', 'site-settings-ai-settings')
                    ->schema([
                        TextInput::make('openai_api_key')
                            ->label('OpenAI API key')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->maxLength(1000)
                            ->helperText('Used for AI rewrite, page review, and file extraction tools. File extraction model and reasoning settings are configured in the app environment.'),
                        Textarea::make('ai_content_prompt')
                            ->label('AI Content Prompt')
                            ->default(AiContentPrompt::DEFAULT)
                            ->rows(6),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                self::section('Social and Video URLs', 'site-settings-social-and-video-urls')
                    ->schema([
                        TextInput::make('livestream_url')
                            ->rules([new HttpOrRelativeUrl])
                            ->maxLength(255),
                        TextInput::make('giving_url')
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
                self::section('Google Tracking', 'site-settings-google-tracking')
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
            ]);
    }

    private static function section(string $heading, string $id): Section
    {
        return Section::make($heading)
            ->id($id)
            ->key($id)
            ->collapsible()
            ->collapsed()
            ->persistCollapsed();
    }
}

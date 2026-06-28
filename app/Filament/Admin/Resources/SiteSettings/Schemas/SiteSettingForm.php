<?php

namespace App\Filament\Admin\Resources\SiteSettings\Schemas;

use App\Filament\Admin\Forms\HtmlCodeTextarea;
use App\Filament\Admin\Forms\ImageUpload;
use App\Filament\Admin\Forms\RichEditorDefaults;
use App\Models\SiteSetting;
use App\Rules\HttpOrRelativeUrl;
use App\Support\AiContentPrompt;
use App\Support\CodeBlockAccess;
use App\Support\RichContent;
use App\Support\SiteDesignPalette;
use App\Support\SiteVariables;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SiteSettingForm
{
    private const SECTION_IDS = [
        'site-settings-organizational-information',
        'site-settings-site-variables',
        'site-settings-site-design-elements',
        'site-settings-dashboard-notes',
        'site-settings-ai-settings',
        'site-settings-social-and-additional-links',
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
                            ->label('Site name')
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
                        TextInput::make('contact_name')
                            ->label('Contact Name')
                            ->maxLength(255),
                        TextInput::make('contact_email')
                            ->label('Contact Email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('contact_phone')
                            ->label('Contact Phone')
                            ->tel()
                            ->maxLength(255),
                        Textarea::make('contact_notes')
                            ->label('Contact Notes')
                            ->rows(2),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                self::section('Dashboard notes', 'site-settings-dashboard-notes')
                    ->schema([
                        RichEditorDefaults::configure(RichEditor::make('dashboard_notes'), withAiRewrite: false)
                            ->label('Dashboard notes')
                            ->dehydrateStateUsing(fn(mixed $state): ?string => RichContent::nullable($state))
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Shown in a movable Dashboard notes widget on the admin dashboard for users and admins. Leave blank to hide the widget.',
                            )
                            ->hintColor('gray')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
                self::section('Social and Additional Links', 'site-settings-social-and-additional-links')
                    ->schema([
                        TextInput::make('facebook_url')
                            ->label('Facebook URL')
                            ->rules([new HttpOrRelativeUrl])
                            ->maxLength(255),
                        TextInput::make('instagram_url')
                            ->label('Instagram URL')
                            ->rules([new HttpOrRelativeUrl])
                            ->maxLength(255),
                        TextInput::make('youtube_url')
                            ->label('YouTube URL')
                            ->rules([new HttpOrRelativeUrl])
                            ->maxLength(255),
                        TextInput::make('tiktok_url')
                            ->label('TikTok URL')
                            ->rules([new HttpOrRelativeUrl])
                            ->maxLength(255),
                        TextInput::make('linkedin_url')
                            ->label('LinkedIn URL')
                            ->rules([new HttpOrRelativeUrl])
                            ->maxLength(255),
                        TextInput::make('google_business_profile_url')
                            ->label('Google Business Profile URL')
                            ->rules([new HttpOrRelativeUrl])
                            ->maxLength(255),
                        TextInput::make('pinterest_url')
                            ->label('Pinterest URL')
                            ->rules([new HttpOrRelativeUrl])
                            ->maxLength(255),
                        TextInput::make('x_url')
                            ->label('X URL')
                            ->rules([new HttpOrRelativeUrl])
                            ->maxLength(255),
                        TextInput::make('threads_url')
                            ->label('Threads URL')
                            ->rules([new HttpOrRelativeUrl])
                            ->maxLength(255),
                        Repeater::make('additional_social_links')
                            ->label('Additional links')
                            ->schema([
                                TextInput::make('label')
                                    ->label('Link text')
                                    ->required()
                                    ->maxLength(80),
                                TextInput::make('url')
                                    ->label('Destination')
                                    ->required()
                                    ->rules([new HttpOrRelativeUrl])
                                    ->maxLength(255),
                                ...ImageUpload::make(
                                    'image_path',
                                    'site-settings/additional-links',
                                    'Image',
                                    fn(ViewField $upload): ViewField => $upload
                                        ->required()
                                        ->helperText('Icon shown in the public footer for this link.')
                                        ->columnSpanFull(),
                                ),
                            ])
                            ->columns(2)
                            ->itemLabel(fn(array $state): ?string => $state['label'] ?? null)
                            ->addActionLabel('Add additional link')
                            ->reorderable()
                            ->dehydrateStateUsing(fn(mixed $state): array => self::normalizeAdditionalSocialLinks($state))
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional footer links with custom icons. The label is used for hover and screen-reader text.',
                            )
                            ->hintColor('gray')
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                self::section('Site Variables', 'site-settings-site-variables')
                    ->schema([
                        Repeater::make('site_variables')
                            ->label('Site variables')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->maxLength(120)
                                    ->afterStateUpdated(fn(Set $set, Get $get, ?string $state): mixed => blank($get('variable'))
                                        ? $set('variable', SiteVariables::normalizeKey($state))
                                        : null),
                                TextInput::make('variable')
                                    ->label('Variable')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->maxLength(120)
                                    ->dehydrateStateUsing(fn(mixed $state): string => SiteVariables::normalizeKey($state))
                                    ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                                    ->validationMessages([
                                        'regex' => 'Use lowercase letters and numbers separated by dashes, such as service-times.',
                                    ])
                                    ->hintIcon(
                                        Heroicon::OutlinedInformationCircle,
                                        'Use this in content as [[variable-name]]. Do not include the brackets in this field.',
                                    )
                                    ->hintColor('gray'),
                                Placeholder::make('token')
                                    ->label('Token')
                                    ->content(fn(Get $get): string => SiteVariables::tokenFor($get('variable') ?: $get('name')) ?? 'Add a variable name')
                                    ->columnSpan(1),
                                HtmlCodeTextarea::html(Textarea::make('value'))
                                    ->label('Value')
                                    ->rows(4)
                                    ->required()
                                    ->hintIcon(
                                        Heroicon::OutlinedInformationCircle,
                                        'Trusted HTML is allowed here because only admins and Code Blocks users can edit site variables.',
                                    )
                                    ->hintColor('gray')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->itemLabel(fn(array $state): ?string => $state['name'] ?? null)
                            ->addActionLabel('Add site variable')
                            ->reorderable()
                            ->disabled(fn(): bool => !CodeBlockAccess::canManage())
                            ->dehydrateStateUsing(fn(mixed $state): array => SiteVariables::normalizeRows($state))
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Reusable sitewide content. Type tokens like [[address]] or [[service-times]] in public content fields.',
                            )
                            ->hintColor('gray')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),

                self::section('Site design and customization', 'site-settings-site-design-elements')
                    ->schema([
                        ...ImageUpload::make(
                            'site_logo_path',
                            'site-settings/logo',
                            'Site logo',
                            fn(ViewField $upload): ViewField => $upload
                                ->helperText('Used in the public header and footer. Leave blank to use the default logo.'),
                        ),
                        ...ImageUpload::make(
                            'default_page_header_image_path',
                            'site-settings/page-header-images',
                            'Default page header image',
                            fn(ViewField $upload): ViewField => $upload
                                ->helperText('Used on public pages when Show page header is on but that page has no Header Image selected.')
                                ->columnSpan(2),
                        ),
                        ColorPicker::make('design_accent_color')
                            ->label('Site accent color')
                            ->hex()
                            ->default(SiteSetting::DEFAULT_DESIGN_ACCENT_COLOR)
                            ->formatStateUsing(fn(mixed $state): string => SiteDesignPalette::normalizeHex($state) ?? SiteSetting::DEFAULT_DESIGN_ACCENT_COLOR)
                            ->required()
                            ->dehydrateStateUsing(fn(mixed $state): ?string => SiteDesignPalette::normalizeHex($state))
                            ->rule('regex:/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/')
                            ->validationMessages([
                                'regex' => 'Enter a valid hex color, such as #17b8ad.',
                            ])
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Primary public-site accent used for buttons, labels, rules, links, and highlights.',
                            )
                            ->hintColor('gray'),
                        ColorPicker::make('design_accent_text_color')
                            ->label('Accent text color')
                            ->hex()
                            ->default(SiteSetting::DEFAULT_DESIGN_ACCENT_TEXT_COLOR)
                            ->formatStateUsing(fn(mixed $state): string => SiteDesignPalette::normalizeHex($state) ?? SiteSetting::DEFAULT_DESIGN_ACCENT_TEXT_COLOR)
                            ->required()
                            ->dehydrateStateUsing(fn(mixed $state): ?string => SiteDesignPalette::normalizeHex($state))
                            ->rule('regex:/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/')
                            ->validationMessages([
                                'regex' => 'Enter a valid hex color, such as #05756f.',
                            ])
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Darker accent used where accent text needs better contrast on light backgrounds.',
                            )
                            ->hintColor('gray'),
                        ColorPicker::make('design_accent_soft_color')
                            ->label('Soft accent color')
                            ->hex()
                            ->default(SiteSetting::DEFAULT_DESIGN_ACCENT_SOFT_COLOR)
                            ->formatStateUsing(fn(mixed $state): string => SiteDesignPalette::normalizeHex($state) ?? SiteSetting::DEFAULT_DESIGN_ACCENT_SOFT_COLOR)
                            ->required()
                            ->dehydrateStateUsing(fn(mixed $state): ?string => SiteDesignPalette::normalizeHex($state))
                            ->rule('regex:/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/')
                            ->validationMessages([
                                'regex' => 'Enter a valid hex color, such as #ddf8f5.',
                            ])
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Light accent tint used for softer public-site backgrounds and highlights.',
                            )
                            ->hintColor('gray'),
                        Repeater::make('design_background_colors')
                            ->label('Background colors')
                            ->default(SiteDesignPalette::defaultBackgroundColors())
                            ->grid([
                                'md' => 2,
                                'xl' => 3,
                            ])
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
                                    ->dehydrateStateUsing(fn(mixed $state): ?string => SiteDesignPalette::normalizeHex($state))
                                    ->rule('regex:/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/')
                                    ->validationMessages([
                                        'regex' => 'Enter a valid hex color, such as #17b8ad.',
                                    ]),
                                Hidden::make('key')
                                    ->dehydrateStateUsing(fn(mixed $state, Get $get): string => SiteDesignPalette::normalizeKey($state) ?? SiteDesignPalette::normalizeKey($get('name')) ?? 'background'),
                            ])
                            ->columns(2)
                            ->itemLabel(fn(array $state): ?string => $state['name'] ?? null)
                            ->addActionLabel('Add background color')
                            ->reorderable()
                            ->dehydrateStateUsing(fn(mixed $state): array => SiteDesignPalette::normalizeBackgroundColors($state) ?: SiteDesignPalette::defaultBackgroundColors())
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'These colors populate the Background color options in page and homepage content blocks.',
                            )
                            ->hintColor('gray')
                            ->columnSpanFull(),
                        HtmlCodeTextarea::css(Textarea::make('custom_css'))
                            ->label('Custom CSS')
                            ->rows(2)
                            ->visible(fn(): bool => CodeBlockAccess::canManage())
                            ->dehydrateStateUsing(fn(?string $state): ?string => filled($state) ? trim($state) : null)
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional public-site CSS override for admins and editors with Code Blocks access. Leave blank to use the standard stylesheet.',
                            )
                            ->hintColor('gray')
                            ->columnSpan(1),
                        HtmlCodeTextarea::html(Textarea::make('header_custom_js'))
                            ->label('Header custom JS')
                            ->rows(2)
                            ->visible(fn(): bool => CodeBlockAccess::canManage())
                            ->dehydrateStateUsing(fn(?string $state): ?string => filled($state) ? trim($state) : null)
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional full script tags rendered immediately before the closing head tag on public pages.',
                            )
                            ->hintColor('gray')
                            ->columnSpanFull(),
                        HtmlCodeTextarea::html(Textarea::make('body_top_custom_js'))
                            ->label('Body top custom JS')
                            ->rows(2)
                            ->visible(fn(): bool => CodeBlockAccess::canManage())
                            ->dehydrateStateUsing(fn(?string $state): ?string => filled($state) ? trim($state) : null)
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional full script tags rendered immediately after the opening body tag on public pages.',
                            )
                            ->hintColor('gray')
                            ->columnSpanFull(),
                        HtmlCodeTextarea::html(Textarea::make('body_bottom_custom_js'))
                            ->label('Body bottom custom JS')
                            ->rows(2)
                            ->visible(fn(): bool => CodeBlockAccess::canManage())
                            ->dehydrateStateUsing(fn(?string $state): ?string => filled($state) ? trim($state) : null)
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional full script tags rendered immediately before the closing body tag on public pages.',
                            )
                            ->hintColor('gray')
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                self::section('Google tracking', 'site-settings-google-tracking')
                    ->schema([
                        TextInput::make('google_tag_manager_id')
                            ->label('Google Tag Manager container ID')
                            ->helperText('Example: GTM-XXXXXXX. This renders the GTM head script and body noscript on public pages.')
                            ->dehydrateStateUsing(fn(?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null)
                            ->rule('nullable')
                            ->rule('regex:/^GTM-[A-Z0-9]+$/i')
                            ->validationMessages([
                                'regex' => 'Enter a valid Google Tag Manager ID, such as GTM-XXXXXXX.',
                            ])
                            ->maxLength(255),
                        TextInput::make('google_analytics_measurement_id')
                            ->label('Google Analytics measurement ID')
                            ->helperText('Example: G-XXXXXXXXXX. Used only when no Google Tag Manager ID is set.')
                            ->dehydrateStateUsing(fn(?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null)
                            ->rule('nullable')
                            ->rule('regex:/^G-[A-Z0-9]+$/i')
                            ->validationMessages([
                                'regex' => 'Enter a valid Google Analytics measurement ID, such as G-XXXXXXXXXX.',
                            ])
                            ->maxLength(255),
                    ])
                    ->columns(2)
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
                            ->label('AI content prompt')
                            ->default(AiContentPrompt::DEFAULT)
                            ->rows(6),
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

    private static function normalizeAdditionalSocialLinks(mixed $links): array
    {
        return collect(is_array($links) ? $links : [])
            ->filter(fn(mixed $link): bool => is_array($link))
            ->map(function (array $link): ?array {
                $label = trim((string) ($link['label'] ?? ''));
                $url = trim((string) ($link['url'] ?? ''));
                $imagePath = self::selectedImagePath($link['image_path'] ?? null);

                if ($label === '' || $url === '' || $imagePath === null) {
                    return null;
                }

                return [
                    'label' => $label,
                    'url' => $url,
                    'image_path' => $imagePath,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private static function selectedImagePath(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = collect($path)->first();
        }

        $path = trim((string) $path);

        return $path === '' ? null : $path;
    }
}

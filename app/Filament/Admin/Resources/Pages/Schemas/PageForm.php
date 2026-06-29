<?php

namespace App\Filament\Admin\Resources\Pages\Schemas;

use App\Filament\Admin\Forms\ContentBlockBuilder;
use App\Filament\Admin\Forms\HtmlCodeTextarea;
use App\Filament\Admin\Forms\ImageUpload;
use App\Filament\Admin\Forms\SlugRebuildAction;
use App\Filament\Admin\Resources\FileDocuments\FileDocumentResource;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Models\FileDocument;
use App\Models\Page;
use App\Rules\HttpOrRelativeUrl;
use App\Rules\PageSlugPath;
use App\Rules\ValidPageParent;
use App\Support\CodeBlockAccess;
use App\Support\RichContent;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class PageForm
{
    private const SECTION_IDS = [
        'pages-redirect',
        'pages-content-blocks',
        'pages-settings',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([

                // Placeholder::make('spacer')
                //     ->hiddenLabel()
                //     ->content(new HtmlString('&nbsp;'))
                //     ->columnSpan(1),

                // Placeholder::make('spacer')
                //     ->hiddenLabel()
                //     ->content(new HtmlString('&nbsp;'))
                //     ->columnSpan(1),

                // Placeholder::make('spacer')
                //     ->hiddenLabel()
                //     ->content(new HtmlString('&nbsp;'))
                //     ->columnSpan(1),

                TextInput::make('title')
                    ->label('Page title')
                    ->required()
                    ->live(onBlur: true)
                    ->maxLength(255)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Main page name shown in the admin and public header area. New pages use this to build the first path.'
                    )
                    ->hintColor('gray')
                    ->afterStateUpdated(fn (Set $set, ?string $state, ?string $operation) => $operation === 'create'
                        ? $set('slug', Str::slug($state))
                        : null),

                TextInput::make('hero_label')
                    ->label('Small label')
                    ->maxLength(255)
                    ->disabled(fn (Get $get): bool => (bool) $get('is_redirect'))
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional short label shown above the page title, such as New Here or Resources.'
                    )
                    ->hintColor('gray'),

                ToggleButtons::make('is_published')
                    ->label('Page is live')
                    ->boolean()
                    ->inline()
                    ->default(false)
                    ->live()
                    ->required()
                    ->extraFieldWrapperAttributes([
                            'style' => 'text-align:right;',
                        ])
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Controls whether visitors can view this page or redirect, subject to publish and expiration dates.'
                    )
                    ->hintColor('gray')
                    ->columnSpan(1),


                HtmlCodeTextarea::html(Textarea::make('intro'))
                    ->label('Intro text')
                    ->rows(2)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional intro text shown near the top of the page when the page header is visible.'
                    )
                    ->disabled(fn (Get $get): bool => (bool) $get('is_redirect'))
                    ->hintColor('gray'),

                HtmlCodeTextarea::html(Textarea::make('message'))
                    ->label('Message')
                    ->rows(2)
                    ->dehydrateStateUsing(fn (mixed $state): ?string => RichContent::nullable($state))
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional message shown in the page header. Plain text is shown as paragraphs; pasted HTML is preserved.'
                    )
                    ->disabled(fn (Get $get): bool => (bool) $get('is_redirect'))
                    ->hintColor('gray')
                    ->columnSpan(2),


                    TextInput::make('slug')
                    ->label('Page path')
                    ->prefix('/')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->rule(new PageSlugPath)
                    ->suffixAction(SlugRebuildAction::make('title'))
                    ->maxLength(255)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Public URL path for this page. Use lowercase words separated by dashes, such as new-here or resources/forms.'
                    )
                    ->hintColor('gray'),

                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_redirect'))
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Lower numbers appear earlier in manual page lists and parent-child page groupings.'
                    )
                    ->hintColor('gray')
                    ->columnSpan(1),


                    ViewField::make('qr_code')
                    ->label('QR Code')
                    ->hiddenLabel()
                    ->view('filament.admin.forms.components.page-qr-code')
                    ->viewData(fn (?Page $record): array => [
                        'qrCode' => $record?->qrCode()->first(),
                    ])
                    ->disabled(fn (?string $operation): bool => $operation === 'edit')
                    ->dehydrated(false)
                    ->columnSpan(1),



                    ToggleButtons::make('is_redirect')
                    ->label('Redirect this page')
                    ->boolean()
                    ->inline()
                    ->live()
                    ->default(false)
                    ->required()
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Use this when this path should forward visitors somewhere else instead of rendering page content.'
                    )
                    ->columnSpan(1)
                    ->hintColor('gray'),

                     
                ViewField::make('section_controls')
                    ->hiddenLabel()
                    ->view('filament.admin.section-controls')
                    ->viewData([
                        'sectionIds' => self::SECTION_IDS,
                    ])
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_redirect'))
                    ->dehydrated(false)
                    ->key('pages-section-controls')
                    ->columnSpan(2),

                self::section('Page details', 'pages-settings', collapsedOnEdit: true)
                    ->description('Controls the order, publish window, header/card graphics, SEO content, page structure, and hierarchy.')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->schema([


                    ...ImageUpload::make(
                            'hero_image_path',
                            'pages/hero-images',
                            'Header image',
                            fn (ViewField $upload): ViewField => $upload
                                ->hintIcon(
                                    Heroicon::OutlinedInformationCircle,
                                    'Optional image used in the page header. Landscape photos usually work best.'
                                )
                                ->columnSpan(2)
                                ->hintColor('gray'),
                        )
                        ,

                        ...ImageUpload::make(
                            'card_image_path',
                            'pages/card-images',
                            'Card image',
                            fn (ViewField $upload): ViewField => $upload
                                ->hintIcon(
                                    Heroicon::OutlinedInformationCircle,
                                    'Optional image used when this page appears in cards, parent-page child lists, or other listing areas. If empty, the header image is used.'
                                )
                                ->hintColor('gray'),
                        ),
                        Select::make('parent_page_id')
                            ->label('Parent page')
                            ->options(fn (?Page $record): array => self::parentPageOptions($record))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->rule(fn (?Page $record): ValidPageParent => new ValidPageParent($record?->getKey()))
                            ->visible(fn (Get $get): bool => ! (bool) $get('is_redirect'))
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional. Makes this page a child of another page, useful for Resources, Forms, or grouped landing pages.'
                            )
                            ->hintColor('gray')
                            ->columnSpan(1),



                        Placeholder::make('direct_child_pages')
                            ->label('Parent to the following pages and files')
                            ->content(fn (?Page $record): HtmlString => new HtmlString(
                                '<div style="
                                    border: 1px solid color-mix(in srgb, currentColor 14%, transparent);
                                    background: color-mix(in srgb, currentColor 4%, transparent);
                                    border-radius: 0.5rem;
                                    padding: 1rem;
                                ">'
                                . self::directChildPagesContent($record)->toHtml()
                                . '</div>'
                            ))
                            ->visible(fn (?Page $record, Get $get): bool => ! (bool) $get('is_redirect'))
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Shows direct child pages and files attached to this page. Edit the child page or file to change or remove its parent.'
                            )
                            ->hintColor('gray')
                            ->columnSpan(2),




                        DateTimePicker::make('publish_at')
                            ->label('Publish at')
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional. Leave empty to allow the page to be visible immediately once Page is live is enabled.'
                            )
                            ->hintColor('gray')
                            ->columnSpan(1),

                        DateTimePicker::make('expires_at')
                            ->label('Expires at')
                            ->afterOrEqual(fn (Get $get): ?string => $get('publish_at'))
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional. Leave empty to keep the live page visible indefinitely.'
                            )
                            ->hintColor('gray')
                            ->columnSpan(2),

                        DateTimePicker::make('featured_at')
                            ->label('Feature start')
                            ->visible(fn (Get $get): bool => filled($get('parent_page_id')))
                            ->afterOrEqual(fn (Get $get): ?string => $get('publish_at'))
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional for child pages. Controls when this page starts being featured under its parent.'
                            )
                            ->hintColor('gray')
                            ->columnSpan(2),

                        DateTimePicker::make('feature_expires_at')
                            ->label('Feature end')
                            ->visible(fn (Get $get): bool => filled($get('parent_page_id')))
                            ->afterOrEqual(fn (Get $get): ?string => $get('featured_at'))
                            ->beforeOrEqual(fn (Get $get): ?string => $get('expires_at'))
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional for child pages. Controls when this page stops being featured under its parent.'
                            )
                            ->hintColor('gray')
                            ->columnSpan(1),

                        TextInput::make('seo_title')
                            ->label('SEO title')
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => self::canManageSeoFields($get))
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional browser and search title. Leave empty to use the page title.'
                            )
                            ->hintColor('gray')
                            ->columnSpan(1),

                        Textarea::make('seo_description')
                            ->label('SEO description')
                            ->rows(1)
                            ->visible(fn (Get $get): bool => self::canManageSeoFields($get))
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Optional short search/social description for this page. Aim for one clear sentence.'
                            )
                            ->hintColor('gray')
                            ->columnSpan(2),
                        ToggleButtons::make('show_site_chrome')
                            ->label('Show navigation')
                            ->boolean()
                            ->inline()
                            ->default(true)
                            ->required()
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Turn off only for standalone campaign, embedded, or special-purpose pages that should not show the normal site frame.'
                            )
                            ->hintColor('gray'),


                        ToggleButtons::make('show_page_header')
                            ->label('Show page header')
                            ->boolean()
                            ->inline()
                            ->default(true)
                            ->required()
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Controls whether the public page shows its title, intro, message, and header image area.'
                            )
                            ->hintColor('gray'),

                        ToggleButtons::make('noindex_nofollow')
                            ->label('Hide from search engines')
                            ->boolean()
                            ->inline()
                            ->default(false)
                            ->required()
                            ->visible(fn (Get $get): bool => self::canManageSeoFields($get))
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Adds a robots noindex, nofollow meta tag to ask search engines not to index or follow links on this page.'
                            )
                            ->hintColor('gray'),

                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_redirect')),

                self::section('Redirect', 'pages-redirect')
                    ->description('Use this page path as a simple forwarding URL for old links, QR codes, campaigns, or moved pages.')
                    ->icon(Heroicon::OutlinedArrowRightCircle)
                    ->schema([
                        Placeholder::make('redirect_inactive_notice')
                            ->label('Redirect inactive')
                            ->content(new HtmlString('<span class="text-sm font-medium text-warning-600 dark:text-warning-400">This redirect is saved but will not work publicly until Page is live is enabled.</span>'))
                            ->visible(fn (Get $get): bool => ! (bool) $get('is_published'))
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Redirects only work publicly when the page is live.'
                            )
                            ->hintColor('gray'),
                        TextInput::make('redirect_url')
                            ->label('Send visitors to')
                            ->placeholder('/new-here')
                            ->rules([new HttpOrRelativeUrl])
                            ->required(fn (Get $get): bool => (bool) $get('is_redirect'))
                            ->maxLength(2048)
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Use a local path like /new-here or a full https:// URL.'
                            )
                            ->hintColor('gray')
                            ->columnSpan(2),

                        ToggleButtons::make('redirect_status_code')
                            ->label('Redirect type')
                            ->options(Page::redirectStatusOptions())
                            ->inline()
                            ->default(Page::REDIRECT_TEMPORARY)
                            ->required()
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Temporary is safest for links that may change. Permanent is for URLs that permanently moved.'
                            )
                            ->hintColor('gray'),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => (bool) $get('is_redirect')),

                self::section('Page content', 'pages-content-blocks')
                    ->description('Build the visible page body here. Each block becomes a public section on the page.')
                    ->icon(Heroicon::OutlinedRectangleGroup)
                    ->iconColor('success')
                    ->extraAttributes([
                        'class' => 'rounded-xl border border-success-500/30 bg-success-50/40 p-6 dark:bg-success-950/10',
                    ])
                    ->schema([
                        ContentBlockBuilder::make('content_blocks', 'pages/content-images', 'Page content', true, withPageBlocks: true)
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'Add and reorder the visible content sections for this page.'
                            )
                            ->hintColor('gray')
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_redirect')),

                /********************************
                self::section('Page Display', 'pages-display', collapsedOnEdit: true)
                    ->description('Controls the public page frame, header copy, listing image, and optional SEO Title/Description.')
                    ->icon(Heroicon::OutlinedRectangleGroup)
                    ->schema([
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_redirect')),
***********************************/
            ]);
    }

    private static function section(string $heading, string $id, bool $collapsedOnEdit = false): Section
    {
        return Section::make($heading)
            ->id($id)
            ->key($id, isInheritable: false)
            ->collapsible()
            ->collapsed(fn (?string $operation): bool => $operation === 'edit' && $collapsedOnEdit)
            ->persistCollapsed(fn (?string $operation): bool => $operation === 'edit');
    }

    public static function parentPageOptions(?Page $record = null): array
    {
        return Page::query()
            ->when($record?->getKey(), fn ($query, int $pageId) => $query->whereKeyNot($pageId))
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'sort_order', 'is_published'])
            ->mapWithKeys(fn (Page $page): array => [
                (string) $page->getKey() => self::parentPageOptionLabel($page),
            ])
            ->all();
    }

    public static function canManageSeoFields(Get $get): bool
    {
        return ! (bool) $get('is_redirect') && CodeBlockAccess::canManage();
    }

    public static function parentPageOptionLabel(Page $page): string
    {
        $status = $page->is_published ? 'Active' : 'Inactive';

        return sprintf('%s (/%s) - %s', $page->title, ltrim((string) $page->slug, '/'), $status);
    }

    public static function directChildPagesContent(?Page $record): HtmlString
    {
        if (blank($record?->getKey())) {
            return new HtmlString('');
        }

        $children = $record->childPages()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'hero_label', 'intro', 'sort_order', 'is_published', 'expires_at']);

        $files = $record->fileDocuments()
            ->orderBy('category')
            ->orderBy('title')
            ->get(['id', 'title', 'file_name', 'category', 'is_published', 'visibility', 'publish_at', 'expires_at', 'current_version_id']);

        if ($children->isEmpty() && $files->isEmpty()) {
            return new HtmlString('<span class="text-sm text-gray-500 dark:text-gray-400">No direct subpages or files currently use this page as a parent.</span>');
        }

        $items = collect($children->map(fn (Page $page): string => self::childPageListItem($page))->all())
            ->merge($files->map(fn (FileDocument $file): string => self::childFileListItem($file))->all())
            ->implode('');

return new HtmlString('<ul style="display: grid; gap: 0.5rem; margin: 0; padding: 0; list-style: none; font-size: 0.875rem; line-height: 1.25rem;">'.$items.'</ul>');    }

    private static function childPageListItem(Page $page): string
    {
        return sprintf(
            '<li style="display: flex; align-items: flex-start; gap: 0.5rem;">%s<span style="display: grid; min-width: 0; gap: 0.125rem;"><span style="min-width: 0;"><span class="text-gray-500 dark:text-gray-400" style="font-weight: 700;">Page:</span> <strong title="%s">%s</strong> <span class="text-gray-500 dark:text-gray-400" title="%s">/%s</span></span><span class="text-gray-500 dark:text-gray-400" style="font-size: 0.75rem; line-height: 1rem;">%s</span></span></li>',
            self::pageActionLinks($page),
            e(self::pageDetailTooltip($page)),
            e($page->title),
            e(self::pageDetailTooltip($page)),
            e(ltrim((string) $page->slug, '/')),
            e(self::pageExpirationLabel($page)),
        );
    }

    private static function pageExpirationLabel(Page $page): string
    {
        return 'Expires: '.($page->expires_at?->format('M j, Y g:i A') ?? 'Not set');
    }

    private static function childFileListItem(FileDocument $file): string
    {
        $url = $file->publicUrl() ?? $file->downloadUrl();
        $path = filled($file->file_name) ? '/files/'.ltrim((string) $file->file_name, '/') : 'No public file path';

        return sprintf(
            '<li style="display: flex; align-items: center; gap: 0.5rem;">%s<span style="min-width: 0;"><span class="text-gray-500 dark:text-gray-400" style="font-weight: 700;">File:</span> <strong title="%s">%s</strong> <span class="text-gray-500 dark:text-gray-400" title="%s">%s</span></span></li>',
            self::fileActionLinks($file, $url),
            e(self::fileDetailTooltip($file)),
            e($file->title),
            e(self::fileDetailTooltip($file)),
            e($path),
        );
    }

    private static function pageActionLinks(Page $page): string
    {
        return sprintf(
            '<span style="display: inline-flex; flex-shrink: 0; align-items: center; gap: .04rem;">%s%s%s</span>',
            self::pageIconLink(
                href: (string) $page->publicUrl(),
                label: 'View page',
                color: '#9ca3af',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H18m0 0v4.5M18 6l-7.5 7.5M6 8.25A2.25 2.25 0 0 1 8.25 6h2.25M6 8.25v7.5A2.25 2.25 0 0 0 8.25 18h7.5A2.25 2.25 0 0 0 18 15.75v-2.25" />',
            ),
            self::pageIconLink(
                href: PageResource::getUrl('edit', ['record' => $page]),
                label: 'Edit page',
                color: '#f59e0b',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.651 1.651a1.875 1.875 0 0 1 0 2.652L8.625 18.678 4.5 19.5l.822-4.125 9.888-9.888a1.875 1.875 0 0 1 2.652 0Z" />',
            ),
            self::pageStatusIcon($page),
        );
    }

    private static function fileActionLinks(FileDocument $file, string $url): string
    {
        return sprintf(
            '<span style="display: inline-flex; flex-shrink: 0; align-items: center; gap: .04rem;">%s%s%s</span>',
            self::pageIconLink(
                href: $url,
                label: 'View file',
                color: '#9ca3af',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />',
            ),
            self::pageIconLink(
                href: FileDocumentResource::getUrl('edit', ['record' => $file]),
                label: 'Edit file',
                color: '#f59e0b',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.651 1.651a1.875 1.875 0 0 1 0 2.652L8.625 18.678 4.5 19.5l.822-4.125 9.888-9.888a1.875 1.875 0 0 1 2.652 0Z" />',
            ),
            self::fileStatusIcon($file),
        );
    }

    private static function pageIconLink(string $href, string $label, string $color, string $icon): string
    {
        return sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer" title="%s" aria-label="%s" class="rounded-md transition hover:bg-gray-100 dark:hover:bg-white/10" style="display: inline-flex; width: 1.5rem; height: 1.5rem; align-items: center; justify-content: center; flex-shrink: 0; color: %s;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="display: block; width: 1rem; height: 1rem; max-width: 1rem; max-height: 1rem; flex-shrink: 0;">%s</svg></a>',
            e($href),
            e($label),
            e($label),
            e($color),
            $icon,
        );
    }

    private static function pageStatusIcon(Page $page): string
    {
        if ($page->is_published) {
            return self::pageStatusIconMarkup(
                label: 'Active',
                color: '#22c55e',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
            );
        }

        return self::pageStatusIconMarkup(
            label: 'Inactive',
            color: '#ef4444',
            icon: '<path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
        );
    }

    private static function fileStatusIcon(FileDocument $file): string
    {
        if ($file->isLive()) {
            return self::pageStatusIconMarkup(
                label: $file->isPublic() ? 'Live public file' : 'Live private file',
                color: $file->isPublic() ? '#22c55e' : '#f59e0b',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
            );
        }

        return self::pageStatusIconMarkup(
            label: 'Inactive file',
            color: '#ef4444',
            icon: '<path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
        );
    }

    private static function pageStatusIconMarkup(string $label, string $color, string $icon): string
    {
        return sprintf(
            '<span title="%s" aria-label="%s" style="display: inline-flex; width: 1.5rem; height: 1.5rem; align-items: center; justify-content: center; flex-shrink: 0; color: %s;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="display: block; width: 1rem; height: 1rem; max-width: 1rem; max-height: 1rem; flex-shrink: 0;">%s</svg></span>',
            e($label),
            e($label),
            e($color),
            $icon,
        );
    }

    private static function pageDetailTooltip(Page $page): string
    {
        return sprintf(
            "Small label: %s\nIntro: %s",
            filled($page->hero_label) ? $page->hero_label : 'Not set',
            filled($page->intro) ? $page->intro : 'Not set',
        );
    }

    private static function fileDetailTooltip(FileDocument $file): string
    {
        return sprintf(
            "Category: %s\nVisibility: %s",
            filled($file->category) ? $file->category : 'Not set',
            filled($file->visibility) ? ucfirst((string) $file->visibility) : 'Not set',
        );
    }
}

<?php

namespace App\Filament\Admin\Resources\Pages\Schemas;

use App\Filament\Admin\Forms\ContentBlockBuilder;
use App\Filament\Admin\Forms\ImageUpload;
use App\Filament\Admin\Forms\SlugRebuildAction;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Models\Page;
use App\Rules\HttpOrRelativeUrl;
use App\Rules\PageSlugPath;
use App\Rules\ValidPageParent;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->maxLength(255)
                    ->afterStateUpdated(fn (Set $set, ?string $state, ?string $operation) => $operation === 'create'
                        ? $set('slug', Str::slug($state))
                        : null),
                ToggleButtons::make('is_published')
                    ->label('Make Page Live')
                    ->boolean()
                    ->inline()
                    ->default(false)
                    ->live()
                    ->required(),
                TextInput::make('hero_label')
                    ->label('Small label')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_redirect')),
                TextInput::make('slug')
                    ->prefix('/')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->rule(new PageSlugPath)
                    ->suffixAction(SlugRebuildAction::make('title'))
                    ->maxLength(255),
                Textarea::make('intro')
                    ->rows(1)
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_redirect')),

                ToggleButtons::make('is_redirect')
                    ->label('Redirect this page')
                    ->boolean()
                    ->inline()
                    ->live()
                    ->default(false)
                    ->required(),

                Placeholder::make('redirect_inactive_notice')
                    ->label('Redirect inactive')
                    ->content(new HtmlString('<span class="text-sm font-medium text-warning-600 dark:text-warning-400">This redirect is saved but will not work publicly until Make Page Live is set to Yes.</span>'))
                    ->visible(fn (Get $get): bool => (bool) $get('is_redirect') && ! (bool) $get('is_published'))
                    ->columnSpanFull(),

                DateTimePicker::make('publish_at')
                    ->label('Publish at'),
                DateTimePicker::make('expires_at')
                    ->label('Expires at')
                    ->afterOrEqual(fn (Get $get): ?string => $get('publish_at')),

                ImageUpload::make('hero_image_path', 'pages/hero-images', 'Header Image')
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_redirect')),
                ImageUpload::make('card_image_path', 'pages/card-images', 'Card image')
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_redirect')),

                Section::make('Redirect')
                    ->description('Use this page slug as a simple forwarding URL for old links, QR codes, campaigns, or moved pages.')
                    ->icon(Heroicon::OutlinedArrowRightCircle)
                    ->schema([
                        TextInput::make('redirect_url')
                            ->label('Send visitors to')
                            ->helperText('Use a local path like /new-here or a full https:// URL.')
                            ->placeholder('/new-here')
                            ->rules([new HttpOrRelativeUrl])
                            ->required(fn (Get $get): bool => (bool) $get('is_redirect'))
                            ->maxLength(2048)
                            ->columnSpanFull(),
                        ToggleButtons::make('redirect_status_code')
                            ->label('Redirect type')
                            ->options(Page::redirectStatusOptions())
                            ->helperText('Temporary is safest for links that may change. Permanent should only be used when an old URL has permanently moved.')
                            ->inline()
                            ->default(Page::REDIRECT_TEMPORARY)
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => (bool) $get('is_redirect')),

                Section::make('Page Content Blocks')
                    ->description('Build the visible page body here. Each block becomes a public section on the page.')
                    ->icon(Heroicon::OutlinedRectangleGroup)
                    ->iconColor('success')
                    ->extraAttributes([
                        'class' => 'rounded-xl border border-success-500/30 bg-success-50/40 p-6 dark:bg-success-950/10',
                    ])
                    ->schema([
                        ContentBlockBuilder::make('content_blocks', 'pages/content-images', 'Page Content', true),
                    ])
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_redirect')),
                ToggleButtons::make('show_site_chrome')
                    ->label('Show navigation and footer')
                    ->boolean()
                    ->inline()
                    ->default(true)
                    ->required()
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_redirect')),
                ToggleButtons::make('show_page_header')
                    ->label('Show page header')
                    ->boolean()
                    ->inline()
                    ->default(true)
                    ->required()
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_redirect')),

                TextInput::make('seo_title')
                    ->label('SEO title')
                    ->helperText('Alternative for additional SEO content in the page BROWSER title.')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_redirect')),

                Textarea::make('seo_description')
                    ->helperText('Only for search engines review - not seen by users for SEO rankings.')
                    ->label('SEO description')
                    ->rows(1)
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_redirect')),

                Select::make('parent_page_id')
                    ->label('Parent Page - optional')
                    ->options(fn (?Page $record): array => self::parentPageOptions($record))
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->rule(fn (?Page $record): ValidPageParent => new ValidPageParent($record?->getKey()))
                    ->visible(fn (Get $get): bool => ! (bool) $get('is_redirect')),
                Placeholder::make('direct_child_pages')
                    ->label('Parent to the following child pages')
                    ->content(fn (?Page $record): HtmlString => self::directChildPagesContent($record))
                    ->visible(fn (?Page $record, Get $get): bool => filled($record?->getKey()) && ! (bool) $get('is_redirect')),

            ]);
    }

    public static function parentPageOptions(?Page $record = null): array
    {
        return Page::query()
            ->when($record?->getKey(), fn ($query, int $pageId) => $query->whereKeyNot($pageId))
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'is_published'])
            ->mapWithKeys(fn (Page $page): array => [
                (string) $page->getKey() => self::parentPageOptionLabel($page),
            ])
            ->all();
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
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'hero_label', 'intro', 'is_published']);

        if ($children->isEmpty()) {
            return new HtmlString('<span class="text-sm text-gray-500 dark:text-gray-400">No direct subpages currently use this page as a parent.</span>');
        }

        $items = $children
            ->map(function (Page $page): string {
                return sprintf(
                    '<li style="display: flex; align-items: center; gap: 0.5rem;">%s<span style="min-width: 0;"><strong title="%s">%s</strong> <span class="text-gray-500 dark:text-gray-400" title="%s">/%s</span></span></li>',
                    self::pageActionLinks($page),
                    e(self::pageDetailTooltip($page)),
                    e($page->title),
                    e(self::pageDetailTooltip($page)),
                    e(ltrim((string) $page->slug, '/')),
                );
            })
            ->implode('');

        return new HtmlString('<ul style="display: grid; gap: 1.2rem; margin: 0; padding: 0; list-style: none; font-size: 0.875rem; line-height: 1.25rem;">'.$items.'</ul>');
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
}

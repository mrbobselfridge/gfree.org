<?php

namespace App\Filament\Admin\Resources\Pages\Schemas;

use App\Filament\Admin\Forms\ContentBlockBuilder;
use App\Filament\Admin\Forms\ImageUpload;
use App\Filament\Admin\Forms\SlugRebuildAction;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Models\Page;
use App\Rules\PageSlugPath;
use App\Rules\ValidPageParent;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
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
                    ->required(),
                TextInput::make('hero_label')
                    ->label('Small label')
                    ->maxLength(255),
                TextInput::make('slug')
                    ->prefix('/')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->rule(new PageSlugPath)
                    ->suffixAction(SlugRebuildAction::make('title'))
                    ->maxLength(255),
                Textarea::make('intro')
                    ->rows(1),
                Select::make('parent_page_id')
                    ->label('Parent Page - optional')
                    ->options(fn (?Page $record): array => self::parentPageOptions($record))
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->rule(fn (?Page $record): ValidPageParent => new ValidPageParent($record?->getKey())),
                ImageUpload::make('hero_image_path', 'pages/hero-images', 'Header Image'),
                Placeholder::make('direct_child_pages')
                    ->label('Direct subpages')
                    ->content(fn (?Page $record): HtmlString => self::directChildPagesContent($record))
                    ->visible(fn (?Page $record): bool => filled($record?->getKey())),

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
                    ->columnSpanFull(),
                TextInput::make('seo_title')
                    ->label('SEO title')
                    ->helperText('Alternative for additional SEO content in the page BROWSER title.')
                    ->maxLength(255),

                Textarea::make('seo_description')
                    ->helperText('Only for search engines review - not seen by users for SEO rankings.')
                    ->label('SEO description')
                    ->rows(1),
                ToggleButtons::make('show_site_chrome')
                    ->label('Show navigation and footer')
                    ->boolean()
                    ->inline()
                    ->default(true)
                    ->required(),
                ToggleButtons::make('show_page_header')
                    ->label('Show page header')
                    ->boolean()
                    ->inline()
                    ->default(true)
                    ->required(),

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
                    '<li class="flex items-start gap-2">%s%s<span class="min-w-0"><strong title="%s">%s</strong> <span class="text-gray-500 dark:text-gray-400" title="%s">/%s</span></span></li>',
                    self::pageActionLinks($page),
                    self::pageStatusIcon($page),
                    e(self::pageDetailTooltip($page)),
                    e($page->title),
                    e(self::pageDetailTooltip($page)),
                    e(ltrim((string) $page->slug, '/')),
                );
            })
            ->implode('');

        return new HtmlString('<ul class="space-y-2 text-sm">'.$items.'</ul>');
    }

    private static function pageActionLinks(Page $page): string
    {
        return sprintf(
            '<span class="mt-0.5 flex shrink-0 items-center gap-1.5">%s%s</span>',
            self::pageIconLink(
                href: (string) $page->publicUrl(),
                label: 'View page',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H18m0 0v4.5M18 6l-7.5 7.5M6 8.25A2.25 2.25 0 0 1 8.25 6h2.25M6 8.25v7.5A2.25 2.25 0 0 0 8.25 18h7.5A2.25 2.25 0 0 0 18 15.75v-2.25" />',
            ),
            self::pageIconLink(
                href: PageResource::getUrl('edit', ['record' => $page]),
                label: 'Edit page',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.651 1.651a1.875 1.875 0 0 1 0 2.652L8.625 18.678 4.5 19.5l.822-4.125 9.888-9.888a1.875 1.875 0 0 1 2.652 0Z" />',
            ),
        );
    }

    private static function pageIconLink(string $href, string $label, string $icon): string
    {
        return sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer" title="%s" aria-label="%s" class="inline-flex h-6 w-6 items-center justify-center rounded-md text-gray-500 transition hover:bg-gray-100 hover:text-primary-600 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-primary-400"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">%s</svg></a>',
            e($href),
            e($label),
            e($label),
            $icon,
        );
    }

    private static function pageStatusIcon(Page $page): string
    {
        if ($page->is_published) {
            return '<span title="Active" aria-label="Active" class="mt-1 inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-success-500 text-white"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" /></svg></span>';
        }

        return '<span title="Inactive" aria-label="Inactive" class="mt-1 inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-gray-400 text-white dark:bg-gray-600"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></span>';
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

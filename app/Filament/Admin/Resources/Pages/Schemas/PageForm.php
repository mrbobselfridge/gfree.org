<?php

namespace App\Filament\Admin\Resources\Pages\Schemas;

use App\Filament\Admin\Forms\ContentBlockBuilder;
use App\Filament\Admin\Forms\ImageUpload;
use App\Filament\Admin\Forms\SlugRebuildAction;
use App\Models\Page;
use App\Rules\PageSlugPath;
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
                ToggleButtons::make('show_site_chrome')
                    ->label('Show navigation and footer')
                    ->boolean()
                    ->inline()
                    ->default(true)
                    ->required(),
                Textarea::make('intro')
                    ->rows(1)
                    ->columnSpanFull(),
                ToggleButtons::make('show_page_header')
                    ->label('Show page header')
                    ->boolean()
                    ->inline()
                    ->default(true)
                    ->required(),
                TextInput::make('slug')
                    ->prefix('/')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->rule(new PageSlugPath)
                    ->suffixAction(SlugRebuildAction::make('title'))
                    ->maxLength(255),
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
                ImageUpload::make('hero_image_path', 'pages/hero-images', 'Header Image')
                    ->columnSpanFull(),
                TextInput::make('seo_title')
                    ->label('SEO title')
                    ->helperText('Alternative for additional SEO content in the page BROWSER title.')
                    ->maxLength(255),
                Textarea::make('seo_description')
                    ->helperText('Only for search engines review - not seen by users for SEO rankings.')
                    ->label('SEO description')
                    ->rows(1),

                Select::make('parent_page_id')
                    ->label('Parent Page')
                    ->options(fn (?Page $record): array => self::parentPageOptions($record))
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->rule(fn (?Page $record): ValidPageParent => new ValidPageParent($record?->getKey()))
                    ->helperText('Optional. All other pages are listed, including inactive pages. A page cannot be its own parent or use one of its subpages as its parent.'),
                Placeholder::make('direct_child_pages')
                    ->label('Direct subpages')
                    ->content(fn (?Page $record): HtmlString => self::directChildPagesContent($record))
                    ->visible(fn (?Page $record): bool => filled($record?->getKey()))
                    ->columnSpanFull(),

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
            ->get(['id', 'title', 'slug', 'is_published']);

        if ($children->isEmpty()) {
            return new HtmlString('<span class="text-sm text-gray-500 dark:text-gray-400">No direct subpages currently use this page as a parent.</span>');
        }

        $items = $children
            ->map(function (Page $page): string {
                $status = $page->is_published ? 'Active' : 'Inactive';

                return sprintf(
                    '<li><strong>%s</strong> <span class="text-gray-500 dark:text-gray-400">/%s - %s</span></li>',
                    e($page->title),
                    e(ltrim((string) $page->slug, '/')),
                    e($status),
                );
            })
            ->implode('');

        return new HtmlString('<ul class="list-disc space-y-1 pl-5 text-sm">'.$items.'</ul>');
    }
}

<?php

namespace App\Filament\Admin\Resources\Pages\Schemas;

use App\Filament\Admin\Forms\ContentBlockBuilder;
use App\Filament\Admin\Forms\ImageUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
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
                TextInput::make('hero_label')
                    ->label('Small label')
                    ->maxLength(255),
                Textarea::make('intro')
                    ->rows(1),
                TextInput::make('slug')
                    ->prefix('/')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Section::make('Page Content Blocks')
                    ->description('Build the visible page body here. Each block becomes a public section on the page.')
                    ->icon(Heroicon::OutlinedRectangleGroup)
                    ->iconColor('success')
                    ->extraAttributes([
                        'class' => 'rounded-xl border border-success-500/30 bg-success-50/40 p-6 dark:bg-success-950/10',
                    ])
                    ->schema([
                        ContentBlockBuilder::make('content_blocks', 'pages/content-images'),
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
            ]);
    }
}

<?php

namespace App\Filament\Admin\Resources\Pages\Schemas;

use App\Filament\Admin\Forms\RichEditorDefaults;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
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
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
                ToggleButtons::make('is_published')
                    ->label('Make Page Live')
                    ->boolean()
                    ->inline()
                    ->default(false)
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
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Section::make('Page Content Blocks')
                    ->description('Build the visible page body here. Each block becomes a public section on the page.')
                    ->icon(Heroicon::OutlinedRectangleGroup)
                    ->iconColor('success')
                    ->extraAttributes([
                        'class' => 'rounded-xl border border-success-500/30 bg-success-50/40 p-6 dark:bg-success-950/10',
                    ])
                    ->schema([
                        Builder::make('content_blocks')
                            ->label('Page Content')
                            ->blocks([
                                Block::make('text')
                                    ->label(fn (?array $state): string => self::blockLabel('Text', $state))
                                    ->schema([
                                        TextInput::make('eyebrow')
                                            ->label('Small label')
                                            ->live(onBlur: true)
                                            ->maxLength(80),
                                        TextInput::make('heading')
                                            ->live(onBlur: true)
                                            ->maxLength(255),
                                        RichEditorDefaults::configure(RichEditor::make('body'))
                                            ->required()
                                            ->columnSpanFull(),
                                        Select::make('background')
                                            ->options(self::backgroundOptions())
                                            ->default('white')
                                            ->required(),
                                    ])
                                    ->columns(2),
                                Block::make('image_text')
                                    ->label(fn (?array $state): string => self::blockLabel('Image', $state))
                                    ->schema([
                                        FileUpload::make('image_path')
                                            ->label('Image')
                                            ->image()
                                            ->disk('public')
                                            ->directory('pages/content-images')
                                            ->required(),
                                        TextInput::make('image_alt')
                                            ->label('Image description')
                                            ->maxLength(255),
                                        TextInput::make('eyebrow')
                                            ->label('Small label')
                                            ->live(onBlur: true)
                                            ->maxLength(80),
                                        TextInput::make('heading')
                                            ->live(onBlur: true)
                                            ->maxLength(255),
                                        RichEditorDefaults::configure(RichEditor::make('body'))
                                            ->columnSpanFull(),
                                        TextInput::make('button_label')
                                            ->maxLength(80),
                                        TextInput::make('button_url')
                                            ->helperText('Use a site path like /give or a full https:// URL.')
                                            ->maxLength(255),
                                        Select::make('background')
                                            ->options(self::backgroundOptions())
                                            ->default('white')
                                            ->required(),
                                        Select::make('image_position')
                                            ->options([
                                                'left' => 'Image left',
                                                'right' => 'Image right',
                                                'center' => 'Image center',
                                                'full_width' => 'Image full width',
                                            ])
                                            ->default('left')
                                            ->required(),
                                    ])
                                    ->columns(2),
                                Block::make('cta')
                                    ->label(fn (?array $state): string => self::blockLabel('CTA', $state))
                                    ->schema([
                                        TextInput::make('eyebrow')
                                            ->label('Small label')
                                            ->live(onBlur: true)
                                            ->maxLength(80),
                                        TextInput::make('heading')
                                            ->live(onBlur: true)
                                            ->maxLength(255),
                                        Textarea::make('body')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                        TextInput::make('button_label')
                                            ->required()
                                            ->maxLength(80),
                                        TextInput::make('button_url')
                                            ->required()
                                            ->maxLength(255),
                                        Select::make('background')
                                            ->options(self::backgroundOptions())
                                            ->default('black')
                                            ->required(),
                                    ])
                                    ->columns(2),
                                Block::make('link_cards')
                                    ->label(fn (?array $state): string => self::blockLabel('Cards', $state))
                                    ->schema([
                                        TextInput::make('eyebrow')
                                            ->label('Small label')
                                            ->live(onBlur: true)
                                            ->maxLength(80),
                                        TextInput::make('heading')
                                            ->live(onBlur: true)
                                            ->maxLength(255),
                                        Select::make('background')
                                            ->options(self::backgroundOptions())
                                            ->default('white')
                                            ->required(),
                                        Repeater::make('cards')
                                            ->schema([
                                                TextInput::make('title')
                                                    ->required()
                                                    ->maxLength(160),
                                                Textarea::make('summary')
                                                    ->rows(2),
                                                TextInput::make('url')
                                                    ->required()
                                                    ->maxLength(255),
                                            ])
                                            ->addActionLabel('Add card')
                                            ->columns(3)
                                            ->minItems(1)
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->addActionLabel('Add content block')
                            ->blockNumbers(false)
                            ->collapsible()
                            ->collapsed()
                            ->extraAttributes([
                                'x-on:click.capture' => <<<'JS'
                                    const header = $event.target.closest('.fi-fo-builder-item-header');
                                    const item = header?.closest('.fi-fo-builder-item');

                                    if (! item || ! $el.contains(item) || ! item.classList.contains('fi-collapsed')) {
                                        return;
                                    }

                                    $dispatch('builder-collapse', 'data.content_blocks');
                                    $dispatch('builder-collapse', 'content_blocks');
                                JS,
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                FileUpload::make('hero_image_path')
                    ->image()
                    ->label('Header Image')
                    ->disk('public')
                    ->directory('pages/hero-images')
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

    private static function blockLabel(string $type, ?array $state): string
    {
        $parts = array_filter([
            $type,
            $state['eyebrow'] ?? null,
            $state['heading'] ?? null,
        ], filled(...));

        return implode(' - ', $parts);
    }

    private static function backgroundOptions(): array
    {
        return [
            'white' => 'White',
            'black' => 'Black',
            'teal' => 'Teal',
            'gold' => 'Gold',
            'forest' => 'Forest',
            'clay' => 'Clay',
        ];
    }
}

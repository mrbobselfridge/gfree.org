<?php

namespace App\Filament\Admin\Forms;

use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Support\Icons\Heroicon;

class ContentBlockBuilder
{
    public static function make(
        string $field = 'content_blocks',
        string $imageDirectory = 'pages/content-images',
        string $label = 'Page Content',
        bool $withStarterTextBlock = false,
    ): Builder {
        $builder = Builder::make($field);

        if ($withStarterTextBlock) {
            $builder->default(self::defaultTextBlock());
        }

        return $builder
            ->label($label)
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
                        Select::make('content_width')
                            ->label('Content width')
                            ->options(self::textWidthOptions())
                            ->default('medium')
                            ->afterStateHydrated(function (Select $component, ?string $state): void {
                                if (blank($state) || $state === 'normal') {
                                    $component->state('medium');
                                }
                            })
                            ->required(),
                        Select::make('background')
                            ->options(self::backgroundOptions())
                            ->default('white')
                            ->required(),
                    ])
                    ->columns(2),
                Block::make('image_text')
                    ->label(fn (?array $state): string => self::blockLabel('Image', $state))
                    ->schema([
                        ImageUpload::make('image_path', $imageDirectory, 'Image'),
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
                                'full_width' => 'Image full width',
                                'screen_width' => 'Image screenwidth',
                            ])
                            ->default('left')
                            ->required(),
                    ])
                    ->columns(2),
                Block::make('process_steps')
                    ->label(fn (?array $state): string => self::blockLabel('Process', $state))
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
                            ->default('black')
                            ->required(),
                        Repeater::make('steps')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('summary')
                                    ->rows(2)
                                    ->required(),
                            ])
                            ->addActionLabel('Add step')
                            ->columns(2)
                            ->minItems(1)
                            ->columnSpanFull(),
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
                        RichEditorDefaults::configure(RichEditor::make('body'))
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
                        Select::make('layout')
                            ->options([
                                'content_left' => 'Content left, button right',
                                'content_right' => 'Button left, content right',
                                'button_top' => 'Button top, content bottom',
                                'button_bottom' => 'Content top, button bottom',
                            ])
                            ->default('content_left')
                            ->required(),
                        Select::make('content_width')
                            ->label('Content width')
                            ->options(self::textWidthOptions())
                            ->default('medium')
                            ->afterStateHydrated(function (Select $component, ?string $state): void {
                                if (blank($state) || $state === 'normal') {
                                    $component->state('medium');
                                }
                            })
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
                                Textarea::make('url')
                                    ->label('URL / href')
                                    ->rows(2)
                                    ->helperText('Optional. Leave blank for a non-linked card. If filled, it is used exactly as the href.'),
                            ])
                            ->addActionLabel('Add card')
                            ->columns(3)
                            ->minItems(1)
                            ->columnSpanFull(),
                    ]),
                Block::make('info_strip')
                    ->label(fn (?array $state): string => self::blockLabel('Info Strip', $state))
                    ->schema([
                        Select::make('spacing')
                            ->options([
                                'both' => 'Space above and below',
                                'top' => 'Space above only',
                                'bottom' => 'Space below only',
                                'none' => 'No extra space',
                            ])
                            ->default('both')
                            ->required(),
                        Repeater::make('items')
                            ->schema([
                                TextInput::make('label')
                                    ->live(onBlur: true)
                                    ->maxLength(80),
                                Select::make('source')
                                    ->options([
                                        'custom' => 'Custom value',
                                        'sunday_service_times' => 'Sunday service times',
                                        'office_hours' => 'Office hours',
                                        'address' => 'Address',
                                    ])
                                    ->default('custom')
                                    ->required(),
                                Textarea::make('value')
                                    ->rows(2)
                                    ->maxLength(500),
                            ])
                            ->addActionLabel('Add item')
                            ->columns(3)
                            ->minItems(1)
                            ->maxItems(5)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Block::make('embed')
                    ->label(fn (?array $state): string => self::blockLabel('Embed', $state))
                    ->schema([
                        TextInput::make('heading')
                            ->live(onBlur: true)
                            ->maxLength(255),
                        Select::make('background')
                            ->options(self::backgroundOptions())
                            ->default('white')
                            ->required(),
                        Textarea::make('embed_code')
                            ->label('Embed code')
                            ->rows(8)
                            ->required()
                            ->helperText('Paste trusted embed code, including script tags when the provider requires them.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Block::make('announcements_bar')
                    ->label(fn (?array $state): string => self::blockLabel('Announcements', $state))
                    ->schema([
                        ToggleButtons::make('is_visible')
                            ->label('Show announcements')
                            ->boolean()
                            ->inline()
                            ->default(true)
                            ->required(),
                        Select::make('background')
                            ->options(self::backgroundOptions())
                            ->default('white')
                            ->required(),
                        TextInput::make('heading')
                            ->live(onBlur: true)
                            ->default('Latest at TwyxtCo')
                            ->maxLength(255),
                        TextInput::make('link_label')
                            ->default('View all')
                            ->maxLength(80),
                        TextInput::make('link_url')
                            ->default('/announcements')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->maxItems(1),
            ])
            ->addActionLabel('Add content block')
            ->cloneable()
            ->cloneAction(fn (Action $action): Action => $action
                ->label('Copy')
                ->icon(Heroicon::OutlinedSquare2Stack)
                ->action(function (array $arguments, Builder $component): void {
                    $items = $component->getRawState();
                    $itemKey = $arguments['item'] ?? null;

                    if ($itemKey === null || ! array_key_exists($itemKey, $items)) {
                        return;
                    }

                    $copiedItem = self::markCopiedItem($items[$itemKey]);
                    $newUuid = $component->generateUuid();

                    if ($newUuid) {
                        $items[$newUuid] = $copiedItem;
                    } else {
                        $items[] = $copiedItem;
                    }

                    $component->rawState($items);
                    $component->collapsed(false, shouldMakeComponentCollapsible: false);
                    $component->callAfterStateUpdated();

                    $component->shouldPartiallyRenderAfterActionsCalled() ? $component->partiallyRender() : null;
                }))
            ->extraFieldWrapperAttributes([
                'class' => 'twyxtco-content-block-builder-field',
            ])
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
            ->columnSpanFull();
    }

    public static function defaultTextBlock(): array
    {
        return [
            [
                'type' => 'text',
                'data' => [
                    'background' => 'white',
                    'content_width' => 'medium',
                ],
            ],
        ];
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

    private static function markCopiedItem(array $item): array
    {
        foreach (['heading', 'title', 'label'] as $field) {
            if (filled($item['data'][$field] ?? null)) {
                $item['data'][$field] .= ' copy';
            }
        }

        return $item;
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

    private static function textWidthOptions(): array
    {
        return [
            'small' => 'Small (600px)',
            'medium' => 'Medium (880px)',
            'wide' => 'Large (1180px)',
        ];
    }
}

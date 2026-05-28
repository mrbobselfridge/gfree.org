<?php

namespace App\Filament\Admin\Forms;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class ContentBlockBuilder
{
    public static function make(string $field = 'content_blocks', string $imageDirectory = 'pages/content-images'): Builder
    {
        return Builder::make($field)
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
                            ->directory($imageDirectory),
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
            ->columnSpanFull();
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

<?php

namespace App\Filament\Admin\Forms;

use App\Models\FileDocument;
use App\Support\CodeBlockAccess;
use App\Support\ContentBlocks;
use App\Support\LinkCard;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Throwable;

class ContentBlockBuilder
{
    public static function make(
        string $field = 'content_blocks',
        string $imageDirectory = 'pages/content-images',
        string $label = 'Page Content',
        bool $withStarterTextBlock = false,
        bool $withScheduleFields = false,
    ): Builder {
        $builder = Builder::make($field);

        if ($withStarterTextBlock) {
            $builder->default(fn (?string $operation): array => $operation === 'create' ? self::defaultTextBlock() : []);
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
                        RichEditorDefaults::configure(RichEditor::make('body'), withAiRewrite: false)
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
                        ...self::scheduleFields($withScheduleFields),
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
                        RichEditorDefaults::configure(RichEditor::make('body'), withAiRewrite: false)
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
                        ...self::scheduleFields($withScheduleFields),
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
                        ...self::scheduleFields($withScheduleFields),
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
                        RichEditorDefaults::configure(RichEditor::make('body'), withAiRewrite: false)
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
                        ...self::scheduleFields($withScheduleFields),
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
                                Hidden::make('key')
                                    ->default(fn (): string => LinkCard::newKey())
                                    ->afterStateHydrated(function (Hidden $component, ?string $state): void {
                                        if (blank($state)) {
                                            $component->state(LinkCard::newKey());
                                        }
                                    }),
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(160),
                                Select::make('type')
                                    ->label('Card type')
                                    ->options(fn (): array => LinkCard::typeOptions(CodeBlockAccess::canManage()))
                                    ->default(LinkCard::TYPE_DISPLAY)
                                    ->afterStateHydrated(function (Select $component, ?string $state, Get $get): void {
                                        if (blank($state)) {
                                            $component->state(LinkCard::normalizeType($state, $get('url')));
                                        }
                                    })
                                    ->required()
                                    ->live(),
                                Textarea::make('summary')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Textarea::make('url')
                                    ->label('URL / href')
                                    ->rows(2)
                                    ->helperText('Use a site path like /give or a full https:// URL.')
                                    ->visible(fn (Get $get): bool => in_array($get('type'), [LinkCard::TYPE_LINK_SAME, LinkCard::TYPE_LINK_NEW], true))
                                    ->columnSpanFull(),
                                ImageUpload::make('image_path', $imageDirectory, 'Flip image')
                                    ->visible(fn (Get $get): bool => $get('type') === LinkCard::TYPE_FLIP_IMAGE)
                                    ->columnSpanFull(),
                                TextInput::make('image_alt')
                                    ->label('Image description')
                                    ->maxLength(255)
                                    ->visible(fn (Get $get): bool => $get('type') === LinkCard::TYPE_FLIP_IMAGE),
                                Select::make('image_fit')
                                    ->label('Image sizing')
                                    ->options(LinkCard::imageFitOptions())
                                    ->default('cover')
                                    ->visible(fn (Get $get): bool => $get('type') === LinkCard::TYPE_FLIP_IMAGE),
                                Select::make('image_focus')
                                    ->label('Image focus')
                                    ->options(LinkCard::imageFocusOptions())
                                    ->default('center')
                                    ->visible(fn (Get $get): bool => $get('type') === LinkCard::TYPE_FLIP_IMAGE),
                                TextInput::make('image_zoom')
                                    ->label('Image zoom')
                                    ->numeric()
                                    ->minValue(100)
                                    ->maxValue(200)
                                    ->step(5)
                                    ->suffix('%')
                                    ->default(100)
                                    ->visible(fn (Get $get): bool => $get('type') === LinkCard::TYPE_FLIP_IMAGE),
                                Textarea::make('html')
                                    ->label('Flip HTML')
                                    ->rows(7)
                                    ->helperText('Trusted raw HTML shown on the back of the flip card.')
                                    ->visible(fn (Get $get): bool => CodeBlockAccess::canManage() && $get('type') === LinkCard::TYPE_FLIP_HTML)
                                    ->dehydrated(fn (): bool => CodeBlockAccess::canManage())
                                    ->columnSpanFull(),
                                Placeholder::make('widget_id')
                                    ->label('Widget div ID')
                                    ->content(fn (Get $get): HtmlString => new HtmlString('<code>'.e(LinkCard::widgetId($get('key'))).'</code>'))
                                    ->visible(fn (Get $get): bool => CodeBlockAccess::canManage() && $get('type') === LinkCard::TYPE_JAVASCRIPT_WIDGET)
                                    ->columnSpanFull(),
                                Textarea::make('javascript')
                                    ->label('JavaScript')
                                    ->rows(9)
                                    ->helperText('Trusted JavaScript rendered after the widget div. Mount into the Widget div ID above.')
                                    ->visible(fn (Get $get): bool => CodeBlockAccess::canManage() && $get('type') === LinkCard::TYPE_JAVASCRIPT_WIDGET)
                                    ->dehydrated(fn (): bool => CodeBlockAccess::canManage())
                                    ->columnSpanFull(),
                            ])
                            ->addActionLabel('Add card')
                            ->columns(2)
                            ->minItems(1)
                            ->columnSpanFull(),
                        ...self::scheduleFields($withScheduleFields),
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
                        ...self::scheduleFields($withScheduleFields),
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
                        ...self::scheduleFields($withScheduleFields),
                    ])
                    ->columns(2),
                Block::make('code')
                    ->label(fn (?array $state): string => self::blockLabel('Code', $state))
                    ->maxItems(fn (): ?int => CodeBlockAccess::canManage() ? null : 0)
                    ->schema([
                        TextInput::make('title')
                            ->helperText('Admin label only. This is not shown on the public page.')
                            ->live(onBlur: true)
                            ->disabled(fn (): bool => ! CodeBlockAccess::canManage())
                            ->maxLength(255),
                        Select::make('background')
                            ->label('Background color')
                            ->options(self::backgroundOptions())
                            ->default('white')
                            ->helperText('Ignored when Content width is None.')
                            ->disabled(fn (): bool => ! CodeBlockAccess::canManage())
                            ->required(),
                        Select::make('content_width')
                            ->label('Content width')
                            ->options(self::codeWidthOptions())
                            ->default('medium')
                            ->disabled(fn (): bool => ! CodeBlockAccess::canManage())
                            ->required(),
                        Textarea::make('code')
                            ->label('Code')
                            ->rows(14)
                            ->required()
                            ->helperText('Trusted raw HTML, CSS, or JavaScript. It is rendered directly on the public page.')
                            ->disabled(fn (): bool => ! CodeBlockAccess::canManage())
                            ->columnSpanFull(),
                        ...self::scheduleFields($withScheduleFields),
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
                            ->required()
                            ->columnSpanFull(),
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
                        ...self::scheduleFields($withScheduleFields),
                    ])
                    ->columns(2)
                    ->maxItems(1),
                Block::make('related_content')
                    ->label(fn (?array $state): string => self::blockLabel('Child Cards', $state))
                    ->schema([
                        ToggleButtons::make('is_visible')
                            ->label('Show child cards')
                            ->boolean()
                            ->inline()
                            ->default(true)
                            ->required()
                            ->columnSpanFull(),
                        Select::make('background')
                            ->options(self::backgroundOptions())
                            ->default('white')
                            ->required(),
                        TextInput::make('heading')
                            ->live(onBlur: true)
                            ->default('Child Cards')
                            ->maxLength(255)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?string $old): void {
                                $current = trim((string) $get('listing_slug'));

                                if ($current === '' || $current === Str::slug($old)) {
                                    $set('listing_slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('intro')
                            ->label('Intro')
                            ->maxLength(255),
                        ToggleButtons::make('content_type')
                            ->label('Show')
                            ->options([
                                ContentBlocks::RELATED_CONTENT_TYPE_PAGES => 'All Pages',
                                ContentBlocks::RELATED_CONTENT_TYPE_FILES => 'All Files',
                                ContentBlocks::RELATED_CONTENT_TYPE_BOTH => 'Both',
                            ])
                            ->inline()
                            ->default(ContentBlocks::RELATED_CONTENT_TYPE_BOTH)
                            ->required()
                            ->live(),
                        ToggleButtons::make('display_mode')
                            ->label('Mode')
                            ->options([
                                ContentBlocks::RELATED_CONTENT_MODE_FEATURED => 'Featured/active',
                                ContentBlocks::RELATED_CONTENT_MODE_ALL => 'All live',
                                ContentBlocks::RELATED_CONTENT_MODE_NEWEST => 'Newest live',
                            ])
                            ->inline()
                            ->default(ContentBlocks::RELATED_CONTENT_MODE_FEATURED)
                            ->required(),
                        Select::make('file_categories')
                            ->label('File categories')
                            ->options(fn (): array => FileDocument::categoryOptions())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Leave empty to include all file categories.')
                            ->visible(fn (Get $get): bool => in_array($get('content_type'), [
                                ContentBlocks::RELATED_CONTENT_TYPE_BOTH,
                                ContentBlocks::RELATED_CONTENT_TYPE_FILES,
                            ], true)),
                        TextInput::make('item_limit')
                            ->label('Items shown')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->default(ContentBlocks::RELATED_CONTENT_DEFAULT_LIMIT)
                            ->required(),
                        TextInput::make('link_label')
                            ->label('View more label')
                            ->default('View more')
                            ->maxLength(80),
                        TextInput::make('listing_slug')
                            ->label('Listing URL path')
                            ->helperText('Used for the generated View More page path. Leave as-is unless you need to preserve an existing link.')
                            ->prefix('parent-page/')
                            ->rule('alpha_dash')
                            ->maxLength(80)
                            ->dehydrateStateUsing(fn (?string $state, Get $get): string => Str::slug($state ?: $get('heading') ?: 'child-cards')),
                        ...self::scheduleFields($withScheduleFields),
                    ])
                    ->columns(2),
            ])
            ->addActionLabel('Add content block')
            ->cloneable()
            ->cloneAction(fn (Action $action): Action => $action
                ->label('Copy')
                ->icon(Heroicon::OutlinedSquare2Stack)
                ->visible(fn (array $arguments, Builder $component): bool => self::canUseBuilderActionForItem($component, $arguments['item'] ?? null))
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
            ->deleteAction(fn (Action $action): Action => $action
                ->visible(fn (array $arguments, Builder $component): bool => self::canUseBuilderActionForItem($component, $arguments['item'] ?? null)))
            ->extraFieldWrapperAttributes([
                'class' => 'twyxtco-content-block-builder-field',
            ])
            ->blockNumbers(false)
            ->collapsible()
            ->collapsed(fn (?string $operation): bool => $operation !== 'create')
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
            $state['title'] ?? null,
            self::scheduleLabel($state),
        ], filled(...));

        return implode(' - ', $parts);
    }

    private static function scheduleFields(bool $withScheduleFields): array
    {
        if (! $withScheduleFields) {
            return [];
        }

        return [
            DateTimePicker::make('publish_at')
                ->label('Publish at')
                ->helperText('Optional. Leave empty to show this block immediately.'),
            DateTimePicker::make('expires_at')
                ->label('Expire at')
                ->helperText('Optional. Leave empty to keep this block visible indefinitely.')
                ->afterOrEqual(fn (Get $get): ?string => $get('publish_at')),
        ];
    }

    private static function scheduleLabel(?array $state): ?string
    {
        $publishAt = self::formatScheduleDate($state['publish_at'] ?? null);
        $expiresAt = self::formatScheduleDate($state['expires_at'] ?? null);

        if (! $publishAt && ! $expiresAt) {
            return null;
        }

        return collect([
            $publishAt ? 'Publish: '.$publishAt : null,
            $expiresAt ? 'Expire: '.$expiresAt : null,
        ])
            ->filter()
            ->implode(' / ');
    }

    private static function formatScheduleDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('M j, Y g:i A');
        } catch (Throwable) {
            return (string) $value;
        }
    }

    private static function markCopiedItem(array $item): array
    {
        foreach (['heading', 'title', 'label'] as $field) {
            if (filled($item['data'][$field] ?? null)) {
                $item['data'][$field] .= ' copy';
            }
        }

        if (($item['type'] ?? null) === 'link_cards') {
            $item['data']['cards'] = collect($item['data']['cards'] ?? [])
                ->map(function (array $card): array {
                    $card['key'] = LinkCard::newKey();

                    return $card;
                })
                ->all();
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

    private static function codeWidthOptions(): array
    {
        return [
            'small' => 'Small (600px)',
            'medium' => 'Medium (880px)',
            'wide' => 'Large (1180px)',
            'full' => 'Full (screen width)',
            'none' => 'None (raw output only)',
        ];
    }

    private static function canUseBuilderActionForItem(Builder $component, int|string|null $itemKey): bool
    {
        $items = $component->getRawState();
        $type = $itemKey !== null ? ($items[$itemKey]['type'] ?? null) : null;

        return $type !== 'code' || CodeBlockAccess::canManage();
    }
}

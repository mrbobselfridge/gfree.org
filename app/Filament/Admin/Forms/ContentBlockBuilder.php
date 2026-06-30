<?php

namespace App\Filament\Admin\Forms;

use App\Models\FileDocument;
use App\Models\Page;
use App\Rules\HttpOrRelativeUrl;
use App\Support\CodeBlockAccess;
use App\Support\ContentBlocks;
use App\Support\LinkCard;
use App\Support\SiteDesignPalette;
use App\Support\YoutubeFeedUrl;
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
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Throwable;

class ContentBlockBuilder
{
    public static function make(
        string $field = 'content_blocks',
        string $imageDirectory = 'pages/content-images',
        string $label = 'Page Content',
        bool $withStarterTextBlock = false,
        bool $withScheduleFields = false,
        bool $withPageBlocks = false,
    ): Builder {
        $builder = Builder::make($field);

        if ($withStarterTextBlock) {
            $builder->default(fn (?string $operation): array => $operation === 'create' ? self::defaultTextBlock() : []);
        }

        return $builder
            ->label($label)
            ->hintIcon(
                Heroicon::OutlinedInformationCircle,
                'Add, configure, copy, reorder, or schedule the visible content sections.'
            )
            ->hintColor('gray')
            ->blocks([
                Block::make('text')
                    ->label(fn (?array $state): string => self::blockLabel('Text', $state))
                    ->schema([
                        self::blockNameField(),
                        self::hint(TextInput::make('eyebrow')
                            ->label('Small label'), 'Optional short label shown above the heading.')
                            ->live(onBlur: true)
                            ->maxLength(80)
                            ->columnSpan(1),
                        self::hint(TextInput::make('heading'), 'Main heading for this text section. Leave empty when the body copy should stand alone.')
                            ->live(onBlur: true)
                            ->maxLength(255)
                            ->columnSpan(1),

                        self::hint(Select::make('background')
                            ->label('Background color'), 'Sets the background color for this section.')
                            ->options(self::backgroundOptions())
                            ->default('white')
                            ->required()
                            ->columnSpan(1),

                        self::contentWidthSelect('Controls the maximum readable width of the text on the public page.'),

                        Placeholder::make('text_spacer')
                            ->hiddenLabel()
                            ->content(new HtmlString('&nbsp;'))
                            ->columnSpan(1),

                        self::hint(RichEditorDefaults::configure(RichEditor::make('body'), withAiRewrite: false), 'Main formatted copy shown in this section.')
                            ->columnSpan(3),

                        ...self::scheduleFields($withScheduleFields),
                    ])
                    ->columns(3),
                Block::make('image_text')
                    ->label(fn (?array $state): string => self::blockLabel('Image + Text', $state))
                    ->schema([
                        self::blockNameField(),
                        self::hint(TextInput::make('eyebrow')
                            ->label('Small label'), 'Optional short label shown above the heading.')
                            ->live(onBlur: true)
                            ->maxLength(80)
                            ->columnSpan(1),
                        self::hint(TextInput::make('heading'), 'Main heading for this image and text section.')
                            ->live(onBlur: true)
                            ->maxLength(255)
                            ->columnSpan(1),
                        self::hint(Select::make('background')
                            ->label('Background color'), 'Sets the background color for this section.')
                            ->options(self::backgroundOptions())
                            ->default('white')
                            ->required()
                            ->columnSpan(1),
                        self::contentWidthSelect('Controls the maximum width of this image and text section.', 'wide'),
                        self::hint(Select::make('image_position')
                            ->label('Layout'), 'Choose how the image and text are arranged.')
                            ->options([
                                'left' => 'Image left middle',
                                'right' => 'Image right middle',
                                'left_top' => 'Image left top',
                                'right_top' => 'Image right top',
                                'top' => 'Image top',
                                'bottom' => 'Image bottom',
                                'full_width' => 'Image full width',
                                'screen_width' => 'Image screenwidth',
                            ])
                            ->default('left')
                            ->required()
                            ->columnSpan(1),
                        ...ImageUpload::make(
                            'image_path',
                            $imageDirectory,
                            'Image',
                            fn (ViewField $upload): ViewField => self::hint($upload, 'Optional image displayed with the text. Landscape images usually work best.')
                                ->columnSpan(2),
                        ),
                        self::hint(TextInput::make('image_alt')
                            ->label('Image alt text'), 'Briefly describe the image for accessibility when the image adds meaning. This is not hover text.')
                            ->maxLength(255)
                            ->columnSpan(1),
                        self::hint(RichEditorDefaults::configure(RichEditor::make('body'), withAiRewrite: false), 'Formatted copy shown beside or below the image.')
                            ->columnSpanFull(),
                        self::hint(TextInput::make('button_label')
                            ->label('Button text'), 'Optional button text. Leave empty when no button is needed.')
                            ->maxLength(80)
                            ->columnSpan(1),
                        self::hint(TextInput::make('button_url')
                            ->label('Button destination'), 'Optional button destination. Use a site path like /give or a full https:// URL.')
                            ->helperText('Use a site path like /give or a full https:// URL.')
                            ->maxLength(255)
                            ->columnSpan(2),
                        ...self::scheduleFields($withScheduleFields),
                    ])
                    ->columns(3),

                Block::make('process_steps')
                    ->label(fn (?array $state): string => self::blockLabel('Process List', $state))
                    ->schema([
                        self::blockNameField(),
                        self::hint(TextInput::make('eyebrow')
                            ->label('Small label'), 'Optional short label shown above the heading.')
                            ->live(onBlur: true)
                            ->maxLength(80)
                            ->columnSpan(1),
                        self::hint(TextInput::make('heading'), 'Main heading for the process list.')
                            ->live(onBlur: true)
                            ->maxLength(255)
                            ->columnSpan(1),
                        self::hint(Select::make('background')
                            ->label('Background color'), 'Sets the background color for this section.')
                            ->options(self::backgroundOptions())
                            ->default('black')
                            ->required()
                            ->columnSpan(1),
                        self::contentWidthSelect('Controls the maximum width of this process list.', 'wide'),
                        self::hint(ToggleButtons::make('background_target')
                            ->label('Affected background'), 'Choose whether the selected Background color affects the process steps or the full page band behind them.')
                            ->options([
                                'item' => 'Item',
                                'page' => 'Page',
                            ])
                            ->inline()
                            ->default('page')
                            ->afterStateHydrated(fn (ToggleButtons $component, ?string $state): mixed => blank($state) ? $component->state('page') : null)
                            ->required()
                            ->columnSpan(1),

                        Placeholder::make('text_spacer')
                            ->hiddenLabel()
                            ->content(new HtmlString('&nbsp;'))
                            ->columnSpan(1),

                        self::nestedEntriesRepeater(
                            self::hint(Repeater::make('steps')
                                ->label('Step entries'), 'Add each step in the order it should appear.')
                                ->schema([
                                    self::hint(TextInput::make('title')
                                        ->label('Step label'), 'Short step label.')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(1),
                                    self::hint(HtmlCodeTextarea::html(Textarea::make('summary'))
                                        ->label('Step text'), 'One or two sentences explaining this step.')
                                        ->rows(2)
                                        ->required()
                                        ->columnSpan(2),
                                ])
                                ->addActionLabel('Add step entry')
                                ->columns(3)
                                ->minItems(1)
                                ->columnSpan(2),
                            'Step entry',
                            'title',
                        ),
                        ...self::scheduleFields($withScheduleFields),
                    ])
                    ->columns(3),
                Block::make('cta')
                    ->label(fn (?array $state): string => self::blockLabel('Button + Text', $state))
                    ->schema([
                        self::blockNameField(),
                        self::hint(TextInput::make('eyebrow')
                            ->label('Small label'), 'Optional short label shown above the heading.')
                            ->live(onBlur: true)
                            ->maxLength(80)
                            ->columnSpan(1),
                        self::hint(TextInput::make('heading'), 'Main call-to-action heading.')
                            ->live(onBlur: true)
                            ->maxLength(255)
                            ->columnSpan(1),
                        self::hint(Select::make('background')
                            ->label('Background color'), 'Sets the background color for this section.')
                            ->options(self::backgroundOptions())
                            ->default('black')
                            ->required()
                            ->columnSpan(1),
                        self::contentWidthSelect('Controls the maximum width of this call-to-action section.'),
                        self::hint(Select::make('layout'), 'Choose where the button appears in relation to the text.')
                            ->options([
                                'content_left' => 'Content left, button right',
                                'content_right' => 'Button left, content right',
                                'button_top' => 'Button top, content bottom',
                                'button_bottom' => 'Content top, button bottom',
                            ])
                            ->default('content_left')
                            ->required()
                            ->columnSpan(1),
                        self::hint(TextInput::make('button_label')
                            ->label('Button text'), 'Required button text.')
                            ->required()
                            ->maxLength(80)
                            ->columnSpan(1),
                        self::hint(TextInput::make('button_url')
                            ->label('Button destination'), 'Required button destination. Use a site path like /give or a full https:// URL.')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        self::hint(RichEditorDefaults::configure(RichEditor::make('body'), withAiRewrite: false), 'Supporting copy shown with the button.')
                            ->columnSpan(3),

                        ...self::scheduleFields($withScheduleFields),
                    ])
                    ->columns(3),
                Block::make('link_cards')
                    ->label(fn (?array $state): string => self::blockLabel('Cards', $state))
                    ->schema([
                        self::blockNameField(),
                        self::hint(TextInput::make('eyebrow')
                            ->label('Small label'), 'Optional short label shown above the card group heading.')
                            ->live(onBlur: true)
                            ->maxLength(80)
                            ->columnSpan(1),
                        self::hint(TextInput::make('heading'), 'Main heading for this group of cards.')
                            ->live(onBlur: true)
                            ->maxLength(255)
                            ->columnSpan(1),
                        self::hint(Select::make('background')
                            ->label('Background color'), 'Sets the background color for this card section.')
                            ->options(self::backgroundOptions())
                            ->default('white')
                            ->required()
                            ->columnSpan(1),
                        self::contentWidthSelect('Controls the maximum width of this card section.', 'wide'),
                        self::hint(ToggleButtons::make('background_target')
                            ->label('Affected background'), 'Choose whether the selected Background color affects the cards or the full page band behind them.')
                            ->options([
                                'item' => 'Item',
                                'page' => 'Page',
                            ])
                            ->inline()
                            ->default('page')
                            ->afterStateHydrated(fn (ToggleButtons $component, ?string $state): mixed => blank($state) ? $component->state('page') : null)
                            ->required()
                            ->columnSpan(1),

                        Placeholder::make('text_spacer')
                            ->hiddenLabel()
                            ->content(new HtmlString('&nbsp;'))
                            ->columnSpan(1),

                        self::nestedEntriesRepeater(
                            self::hint(Repeater::make('cards')
                                ->label('Card entries'), 'Add each card in the order it should appear.')
                                ->schema([
                                    Hidden::make('key')
                                        ->default(fn (): string => LinkCard::newKey())
                                        ->afterStateHydrated(function (Hidden $component, ?string $state): void {
                                            if (blank($state)) {
                                                $component->state(LinkCard::newKey());
                                            }
                                        }),
                                    self::hint(TextInput::make('title')
                                        ->label('Card label'), 'Short visible label for the card.')
                                        ->required()
                                        ->maxLength(160)
                                        ->columnSpan(2),
                                    self::hint(Select::make('type')
                                        ->label('Layout'), 'Choose how this card behaves or displays.')
                                        ->options(fn (): array => LinkCard::typeOptions(CodeBlockAccess::canManage()))
                                        ->default(LinkCard::TYPE_DISPLAY)
                                        ->afterStateHydrated(function (Select $component, ?string $state, Get $get): void {
                                            if (blank($state)) {
                                                $component->state(LinkCard::normalizeType($state, $get('url')));
                                            }
                                        })
                                        ->required()
                                        ->live()
                                        ->columnSpan(1),
                                    self::hint(HtmlCodeTextarea::html(Textarea::make('summary'))
                                        ->label('Card text'), 'Short supporting text for the card.')
                                        ->rows(2)
                                        ->columnSpan(2),
                                    self::hint(Select::make('image_fit')
                                        ->label('Image sizing'), 'Controls how the image fills the front of the card.')
                                        ->options(LinkCard::imageFitOptions())
                                        ->default('cover')
                                        ->visible(fn (Get $get): bool => $get('type') === LinkCard::TYPE_FLIP_IMAGE)
                                        ->columnSpan(1),
                                    self::hint(Textarea::make('url')
                                        ->label('Destination'), 'Destination for link cards. Use a site path like /give or a full https:// URL.')
                                        ->rows(1)
                                        ->helperText('Use a site path like /give or a full https:// URL.')
                                        ->visible(fn (Get $get): bool => in_array($get('type'), [
                                            LinkCard::TYPE_LINK_SAME,
                                            LinkCard::TYPE_LINK_NEW,
                                            LinkCard::TYPE_FLIP_IMAGE,
                                            LinkCard::TYPE_FLIP_HTML,
                                        ], true))
                                        ->columnSpanFull(),
                                    ...ImageUpload::make(
                                        'image_path',
                                        $imageDirectory,
                                        'Card back image',
                                        fn (ViewField $upload): ViewField => $upload
                                            ->hintIcon(
                                                Heroicon::OutlinedInformationCircle,
                                                'Image shown on the back of a flip card.'
                                            )
                                            ->hintColor('gray')
                                            ->visible(fn (Get $get): bool => $get('type') === LinkCard::TYPE_FLIP_IMAGE)
                                            ->columnSpan(2),
                                    ),
                                    self::hint(TextInput::make('image_alt')
                                        ->label('Alt text'), 'Briefly describe the card back image for accessibility when it adds meaning.')
                                        ->maxLength(255)
                                        ->visible(fn (Get $get): bool => $get('type') === LinkCard::TYPE_FLIP_IMAGE)
                                        ->columnSpan(1),

                                    self::hint(TextInput::make('image_focus_x')
                                        ->label('Horizontal position'), 'Slide the cropped image left or right. 0% keeps the left edge in view; 100% keeps the right edge in view.')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->step(5)
                                        ->suffix('%')
                                        ->default(50)
                                        ->afterStateHydrated(function (TextInput $component, mixed $state, Get $get): void {
                                            if (blank($state)) {
                                                $component->state(LinkCard::legacyImageFocusPercent($get('image_focus'), 'x'));
                                            }
                                        })
                                        ->visible(fn (Get $get): bool => $get('type') === LinkCard::TYPE_FLIP_IMAGE)
                                        ->columnSpan(1),
                                    self::hint(TextInput::make('image_focus_y')
                                        ->label('Vertical position'), 'Slide the cropped image up or down. 0% keeps the top edge in view; 100% keeps the bottom edge in view.')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->step(5)
                                        ->suffix('%')
                                        ->default(50)
                                        ->afterStateHydrated(function (TextInput $component, mixed $state, Get $get): void {
                                            if (blank($state)) {
                                                $component->state(LinkCard::legacyImageFocusPercent($get('image_focus'), 'y'));
                                            }
                                        })
                                        ->visible(fn (Get $get): bool => $get('type') === LinkCard::TYPE_FLIP_IMAGE)
                                        ->columnSpan(1),
                                    self::hint(TextInput::make('image_zoom')
                                        ->label('Zoom'), 'Increase only when the image needs tighter cropping.')
                                        ->numeric()
                                        ->minValue(100)
                                        ->maxValue(200)
                                        ->step(5)
                                        ->suffix('%')
                                        ->default(100)
                                        ->visible(fn (Get $get): bool => $get('type') === LinkCard::TYPE_FLIP_IMAGE)
                                        ->columnSpan(1),
                                    self::hint(HtmlCodeTextarea::html(Textarea::make('html'))
                                        ->label('Card back HTML'), 'Trusted raw HTML shown on the back of the flip card.')
                                        ->rows(7)
                                        ->helperText('Trusted raw HTML shown on the back of the flip card.')
                                        ->visible(fn (Get $get): bool => CodeBlockAccess::canManage() && $get('type') === LinkCard::TYPE_FLIP_HTML)
                                        ->dehydrated(fn (): bool => CodeBlockAccess::canManage())
                                        ->columnSpanFull(),
                                    self::hint(HtmlCodeTextarea::javascript(Textarea::make('javascript'))
                                        ->label('JavaScript widget'), 'Trusted JavaScript rendered after the widget div. Mount into the Widget div ID above.')
                                        ->rows(4)
                                        ->helperText('Trusted JavaScript rendered after the widget div. Mount into the Widget div ID above.')
                                        ->visible(fn (Get $get): bool => CodeBlockAccess::canManage() && $get('type') === LinkCard::TYPE_JAVASCRIPT_WIDGET)
                                        ->dehydrated(fn (): bool => CodeBlockAccess::canManage())
                                        ->columnSpan(2),
                                    self::hint(Placeholder::make('widget_id')
                                        ->label('Widget div ID'), 'Use this ID as the mount target for the JavaScript below.')
                                        ->content(fn (Get $get): HtmlString => new HtmlString('<code>'.e(LinkCard::widgetId($get('key'))).'</code>'))
                                        ->visible(fn (Get $get): bool => CodeBlockAccess::canManage() && $get('type') === LinkCard::TYPE_JAVASCRIPT_WIDGET)
                                        ->columnSpan(1),
                                ])
                                ->addActionLabel('Add card entry')
                                ->columns(3)
                                ->minItems(1)
                                ->columnSpan(2),
                            'Card entry',
                            'title',
                        ),
                        ...self::scheduleFields($withScheduleFields),
                    ])
                    ->columns(3),
                Block::make('info_strip')
                    ->label(fn (?array $state): string => self::blockLabel('Strip', $state))
                    ->schema([
                        self::blockNameField(),
                        self::hint(Select::make('spacing')
                            ->label('Layout'), 'Choose extra spacing around the strip.')
                            ->options([
                                'both' => 'Space above and below',
                                'top' => 'Space above only',
                                'bottom' => 'Space below only',
                                'none' => 'No extra space',
                            ])
                            ->default('both')
                            ->required()
                            ->columnSpan(2),
                        self::hint(Select::make('background')
                            ->label('Background color'), 'Sets the background color for this strip.')
                            ->options(self::backgroundOptions())
                            ->default('white')
                            ->afterStateHydrated(fn (Select $component, ?string $state): mixed => blank($state) ? $component->state('white') : null)
                            ->required()
                            ->columnSpan(1),
                        self::contentWidthSelect('Controls the maximum width of this info strip.', 'wide'),
                        self::hint(ToggleButtons::make('background_target')
                            ->label('Affected background'), 'Choose whether the selected Background color affects the strip items or the full page band behind them.')
                            ->options([
                                'item' => 'Item',
                                'page' => 'Page',
                            ])
                            ->inline()
                            ->default('item')
                            ->afterStateHydrated(fn (ToggleButtons $component, ?string $state): mixed => blank($state) ? $component->state('item') : null)
                            ->required()
                            ->columnSpan(1),

                        Placeholder::make('text_spacer')
                            ->hiddenLabel()
                            ->content(new HtmlString('&nbsp;'))
                            ->columnSpan(1),

                        self::nestedEntriesRepeater(
                            self::hint(Repeater::make('items')
                                ->label('Strip entries'), 'Add up to five compact facts, links, or contact details.')
                                ->schema([
                                    self::hint(TextInput::make('label')
                                        ->label('Strip label'), 'Short label for this strip entry.')
                                        ->live(onBlur: true)
                                        ->maxLength(80)
                                        ->columnSpan(1),
                                    self::hint(HtmlCodeTextarea::html(Textarea::make('value'))
                                        ->label('Strip text'), 'Text shown with the label. Site variables like [[address]] are allowed.')
                                        ->rows(2)
                                        ->maxLength(500)
                                        ->columnSpan(2),
                                ])
                                ->addActionLabel('Add strip entry')
                                ->columns(3)
                                ->minItems(1)
                                ->maxItems(5)
                                ->columnSpan(2),
                            'Strip entry',
                            'label',
                        ),

                        ...self::scheduleFields($withScheduleFields),
                    ])
                    ->columns(3),
                Block::make('embed')
                    ->label(fn (?array $state): string => self::blockLabel('Embedded', $state))
                    ->schema([
                        self::blockNameField(),
                        self::hint(TextInput::make('eyebrow')
                            ->label('Small label'), 'Optional short label shown above the heading.')
                            ->live(onBlur: true)
                            ->maxLength(80)
                            ->columnSpan(1),
                        self::hint(TextInput::make('heading'), 'Optional heading displayed above the embedded content.')
                            ->live(onBlur: true)
                            ->maxLength(255)
                            ->columnSpan(1),
                        self::hint(Select::make('background')
                            ->label('Background color'), 'Sets the background color for this embedded section.')
                            ->options(self::backgroundOptions())
                            ->default('white')
                            ->required()
                            ->columnSpan(1),
                        self::contentWidthSelect('Controls the maximum width of this embedded content.'),
                        Placeholder::make('text_spacer')
                            ->hiddenLabel()
                            ->content(new HtmlString('&nbsp;'))
                            ->columnSpan(1),

                        self::hint(HtmlCodeTextarea::html(Textarea::make('embed_code'))
                            ->label('Embed code'), 'Paste trusted embed code, including script tags when the provider requires them.')
                            ->rows(3)
                            ->required()
                            ->helperText('Paste trusted embed code, including script tags when the provider requires them.')
                            ->columnSpanFull(),
                        ...self::scheduleFields($withScheduleFields),
                    ])
                    ->columns(3),
                Block::make('code')
                    ->label(fn (?array $state): string => self::blockLabel('Code', $state))
                    ->maxItems(fn (): ?int => CodeBlockAccess::canManage() ? null : 0)
                    ->schema([
                        self::hint(TextInput::make('title')
                            ->label('Block name'), 'Admin label only. This is not shown on the public page.')
                            ->live(onBlur: true)
                            ->disabled(fn (): bool => ! CodeBlockAccess::canManage())
                            ->maxLength(255)
                            ->columnSpan(1),
                        self::hint(TextInput::make('eyebrow')
                            ->label('Small label'), 'Optional short label shown above the heading when the code block uses a wrapper.')
                            ->live(onBlur: true)
                            ->disabled(fn (): bool => ! CodeBlockAccess::canManage())
                            ->maxLength(80)
                            ->columnSpan(1),
                        self::hint(TextInput::make('heading'), 'Optional heading shown above the custom code when the code block uses a wrapper.')
                            ->live(onBlur: true)
                            ->disabled(fn (): bool => ! CodeBlockAccess::canManage())
                            ->maxLength(255)
                            ->columnSpan(1),
                        self::hint(Select::make('background')
                            ->label('Background color'), 'Sets the wrapper background. Ignored when Content width is None.')
                            ->options(self::backgroundOptions())
                            ->default('white')
                            ->helperText('Ignored when Content width is None.')
                            ->disabled(fn (): bool => ! CodeBlockAccess::canManage())
                            ->required()
                            ->columnSpan(1),
                        self::hint(Select::make('content_width')
                            ->label('Content width'), 'Controls the wrapper width for the custom code output.')
                            ->options(self::codeWidthOptions())
                            ->default('medium')
                            ->disabled(fn (): bool => ! CodeBlockAccess::canManage())
                            ->required()
                            ->columnSpan(1),
                        Placeholder::make('text_spacer')
                            ->hiddenLabel()
                            ->content(new HtmlString('&nbsp;'))
                            ->columnSpan(1),
                        self::hint(HtmlCodeTextarea::mixed(Textarea::make('code'))
                            ->label('Custom code'), 'Trusted raw HTML, CSS, or JavaScript. It is rendered directly on the public page.')
                            ->rows(3)
                            ->required()
                            ->helperText('Trusted raw HTML, CSS, or JavaScript. It is rendered directly on the public page.')
                            ->disabled(fn (): bool => ! CodeBlockAccess::canManage())
                            ->columnSpanFull(),
                        ...self::scheduleFields($withScheduleFields),
                    ])
                    ->columns(3),
                ...self::pageOnlyBlocks($withPageBlocks, $withScheduleFields),
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

    private static function pageOnlyBlocks(bool $withPageBlocks, bool $withScheduleFields): array
    {
        if (! $withPageBlocks) {
            return [];
        }

        return [
            Block::make('related_content')
                ->label(fn (?array $state): string => self::blockLabel('Child Cards', $state))
                ->schema([
                    self::hint(ToggleButtons::make('enable_search')
                        ->label('Enable search'), 'Shows a Search box that filters this child listing by page names, file names, tags, descriptions, and related content.')
                        ->boolean()
                        ->inline()
                        ->default(true)
                        ->required()
                        ->columnSpan(1),

                    // TextInput::make('intro')
                    //     ->label('Intro')
                    //     ->maxLength(255),
                    self::hint(ToggleButtons::make('display_mode')
                        ->label('Listing mode'), 'Featured/active shows child pages within their feature window; All live shows every live child page.')
                        ->options([
                            ContentBlocks::RELATED_CONTENT_MODE_FEATURED => 'Featured/active',
                            ContentBlocks::RELATED_CONTENT_MODE_ALL => 'All live',
                        ])
                        ->inline()
                        ->default(ContentBlocks::RELATED_CONTENT_MODE_FEATURED)
                        ->afterStateHydrated(function (Set $set, Get $get, ?string $state): void {
                            if ($state !== ContentBlocks::RELATED_CONTENT_MODE_NEWEST) {
                                return;
                            }

                            $set('display_mode', ContentBlocks::RELATED_CONTENT_MODE_ALL);

                            if (blank($get('sort_preset'))) {
                                $set('sort_preset', ContentBlocks::RELATED_CONTENT_SORT_PUBLISHED_ORDER_RANDOM);
                            }
                        })
                        ->dehydrateStateUsing(fn (?string $state): string => $state === ContentBlocks::RELATED_CONTENT_MODE_NEWEST
                            ? ContentBlocks::RELATED_CONTENT_MODE_ALL
                            : ($state ?: ContentBlocks::RELATED_CONTENT_MODE_FEATURED))
                        ->required()
                        ->columnSpan(1),

                    self::hint(ToggleButtons::make('is_visible')
                        ->label('Listing is live'), 'Turn this off to keep the block configured without showing it publicly.')
                        ->boolean()
                        ->inline()
                        ->default(true)
                        ->required()
                        ->columnSpan(1),

                    self::blockNameField(),
                    self::hint(TextInput::make('eyebrow')
                        ->label('Small label'), 'Optional short label shown above the heading.')
                        ->live(onBlur: true)
                        ->maxLength(80)
                        ->columnSpan(1),
                    self::hint(TextInput::make('heading'), 'Optional heading displayed above the child cards.')
                        ->live(onBlur: true)
                        ->maxLength(255)
                        ->columnSpan(1),
                    self::hint(Select::make('background')
                        ->label('Background color'), 'Sets the background color for this listing section.')
                        ->options(self::backgroundOptions())
                        ->default('white')
                        ->required()
                        ->columnSpan(1),
                    self::contentWidthSelect('Controls the maximum width of this child listing.', 'wide'),
                    self::hint(Select::make('layout')
                        ->label('Layout'), 'Choose how child pages and files are displayed.')
                        ->options(fn (): array => ContentBlocks::relatedContentLayoutOptions())
                        ->default(ContentBlocks::RELATED_CONTENT_LAYOUT_CARD_GRID)
                        ->live()
                        ->native(false)
                        ->required()
                        ->columnSpan(1),
                    self::hint(Select::make('associated_parent_page_id')
                        ->label('Associated parent page'), 'Choose the page whose direct child pages and files should feed this card block.')
                        ->options(fn (mixed $record): array => self::associatedParentPageOptions($record instanceof Page ? $record : null))
                        ->default(function (mixed $record): ?int {
                            $page = $record instanceof Page ? $record : null;

                            return self::pageHasRelatedListingSource($page) ? $page?->getKey() : null;
                        })
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->required()
                        ->rule(fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                            if (filled($value) && self::pageHasRelatedListingSource(Page::query()->find($value))) {
                                return;
                            }

                            $fail('Choose an associated parent page that has child pages or files.');
                        })
                        ->columnSpan(1),

                    self::hint(TextInput::make('item_limit')
                        ->label('Items shown'), 'Number of cards shown initially and added with each Load more click.')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(50)
                        ->default(ContentBlocks::RELATED_CONTENT_DEFAULT_LIMIT)
                        ->required()
                        ->columnSpan(1),

                    self::hint(Select::make('sort_preset')
                        ->label('Sort by'), 'Controls the order of child pages and files before the Load more button reveals additional items.')
                        ->options(fn (): array => ContentBlocks::relatedContentSortOptions())
                        ->afterStateHydrated(function (Select $component, mixed $state): void {
                            $normalized = ContentBlocks::normalizeRelatedContentSortPreset($state);

                            if ($normalized !== $state) {
                                $component->state($normalized);
                            }
                        })
                        ->default(ContentBlocks::RELATED_CONTENT_SORT_ORDER_RANDOM)
                        ->native(false)
                        ->required()
                        ->columnSpan(1),
                    self::hint(ToggleButtons::make('content_type')
                        ->label('Content type'), 'Choose whether this block lists child pages, attached files, or both.')
                        ->options([
                            ContentBlocks::RELATED_CONTENT_TYPE_PAGES => 'All Pages',
                            ContentBlocks::RELATED_CONTENT_TYPE_FILES => 'All Files',
                            ContentBlocks::RELATED_CONTENT_TYPE_BOTH => 'Both',
                        ])
                        ->inline()
                        ->default(ContentBlocks::RELATED_CONTENT_TYPE_BOTH)
                        ->required()
                        ->live()
                        ->columnSpan(1),

                    self::hint(Select::make('file_categories')
                        ->label('File categories'), 'Leave empty to include all file categories.')
                        ->options(fn (): array => FileDocument::categoryOptions())
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->helperText('Leave empty to include all file categories.')
                        ->disabled(fn (Get $get): bool => ! in_array($get('content_type'), [
                            ContentBlocks::RELATED_CONTENT_TYPE_BOTH,
                            ContentBlocks::RELATED_CONTENT_TYPE_FILES,
                        ], true))
                        ->columnSpan(2),

                    ...self::scheduleFields($withScheduleFields),

                    self::hint(TextInput::make('carousel_auto_delay_seconds')
                        ->label('Auto-rotate delay')
                        ->numeric()
                        ->minValue(5)
                        ->maxValue(180)
                        ->default(ContentBlocks::RELATED_CONTENT_DEFAULT_AUTO_DELAY_SECONDS)
                        ->formatStateUsing(fn (mixed $state): int => is_numeric($state)
                            ? (int) $state
                            : ContentBlocks::RELATED_CONTENT_DEFAULT_AUTO_DELAY_SECONDS)
                        ->dehydrateStateUsing(fn (mixed $state): int => is_numeric($state)
                            ? max(1, (int) $state)
                            : ContentBlocks::RELATED_CONTENT_DEFAULT_AUTO_DELAY_SECONDS)
                        ->nullable()
                        ->dehydrated()
                        ->disabled(fn (Get $get): bool => ! ($get('layout') === ContentBlocks::RELATED_CONTENT_LAYOUT_CARD_CAROUSEL_AUTO))
                        ->columnSpan(1), 'Seconds to wait before moving to the next card when Layout is Card Carousel Auto.'),

                ])
                ->columns(3),
            Block::make('youtube_feed')
                ->label(fn (?array $state): string => self::blockLabel('YouTube Feed', $state))
                ->schema([
                    self::blockNameField(),
                    self::hint(TextInput::make('eyebrow')
                        ->label('Small label'), 'Optional short label shown above the heading.')
                        ->live(onBlur: true)
                        ->maxLength(80)
                        ->columnSpan(1),
                    self::hint(TextInput::make('heading'), 'Optional heading displayed above the YouTube feed.')
                        ->live(onBlur: true)
                        ->maxLength(255)
                        ->columnSpan(1),
                    self::hint(Select::make('background')
                        ->label('Background color'), 'Sets the background color for this YouTube feed.')
                        ->options(self::backgroundOptions())
                        ->default('white')
                        ->afterStateHydrated(fn (Select $component, ?string $state): mixed => blank($state) ? $component->state('white') : null)
                        ->required()
                        ->columnSpan(1),
                    self::contentWidthSelect('Controls the maximum width of this YouTube feed.', 'wide'),

                    self::hint(TextInput::make('item_limit')
                        ->label('Items shown'), 'Maximum number of recent videos to show in this block.')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(50)
                        ->default(ContentBlocks::YOUTUBE_FEED_DEFAULT_LIMIT)
                        ->required()
                        ->columnSpan(1),

                    self::hint(TextInput::make('youtube_channel_url')
                        ->label('YouTube channel URL'), 'Paste the public YouTube channel URL. The RSS feed URL is filled automatically when possible.')
                        ->helperText('Paste the public YouTube channel URL. The RSS feed URL is filled automatically when a channel ID can be found.')
                        ->rules([new HttpOrRelativeUrl])
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            $feedUrl = YoutubeFeedUrl::fromChannelUrl($state);

                            if ($feedUrl) {
                                $set('youtube_feed_url', $feedUrl);
                            }
                        })
                        ->columnSpan(1),
                    self::hint(TextInput::make('youtube_feed_url')
                        ->label('YouTube RSS feed URL'), 'Optional fallback RSS feed URL when the channel URL cannot be resolved automatically.')
                        ->helperText('Optional. Paste a YouTube RSS feed URL when the channel URL cannot be resolved automatically.')
                        ->rules([new HttpOrRelativeUrl])
                        ->columnSpan(1),
                    self::hint(TextInput::make('youtube_link_label')
                        ->label('YouTube link text'), 'Link text shown for opening more videos on YouTube.')
                        ->default('View more on YouTube')
                        ->maxLength(255)
                        ->columnSpan(1),

                    ...self::scheduleFields($withScheduleFields),
                ])
                ->columns(3),
        ];
    }

    private static function blockLabel(string $type, ?array $state): string
    {
        if (filled($state['title'] ?? null)) {
            return collect([$type, $state['title'], self::scheduleLabel($state)])
                ->filter()
                ->implode(' - ');
        }

        $parts = array_filter([
            $type,
            $state['eyebrow'] ?? null,
            $state['heading'] ?? null,
            $state['title'] ?? null,
            self::scheduleLabel($state),
        ], filled(...));

        return implode(' - ', $parts);
    }

    private static function blockNameField(): TextInput
    {
        return self::hint(TextInput::make('title')
            ->label('Block name'), 'Optional admin-only name used to identify this block in the editor. It is not shown on the public page.')
            ->live(onBlur: true)
            ->maxLength(255)
            ->columnSpan(1);
    }

    private static function hint(mixed $component, string $tooltip): mixed
    {
        return $component
            ->hintIcon(Heroicon::OutlinedInformationCircle, $tooltip)
            ->hintColor('gray');
    }

    private static function nestedEntriesRepeater(Repeater $repeater, string $fallbackLabel, string $labelField): Repeater
    {
        return $repeater
            ->collapsible()
            ->collapsed()
            ->truncateItemLabel()
            ->itemLabel(fn (array $state): string => self::entryLabel($fallbackLabel, $state[$labelField] ?? null))
            ->expandAllAction(fn (Action $action): Action => $action->label('Expand all entries'))
            ->collapseAllAction(fn (Action $action): Action => $action->label('Collapse all entries'));
    }

    private static function entryLabel(string $fallbackLabel, mixed $value): string
    {
        $label = trim(strip_tags((string) $value));

        return filled($label) ? $label : $fallbackLabel;
    }

    private static function associatedParentPageOptions(?Page $record): array
    {
        return Page::query()
            ->where(fn ($query) => $query
                ->whereHas('childPages')
                ->orWhereHas('fileDocuments'))
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'sort_order', 'is_published'])
            ->mapWithKeys(fn (Page $page): array => [
                (string) $page->getKey() => self::associatedParentPageOptionLabel($page, $record),
            ])
            ->all();
    }

    private static function associatedParentPageOptionLabel(Page $page, ?Page $record): string
    {
        $status = $page->is_published ? 'Active' : 'Inactive';
        $current = $record?->is($page) ? ' - Current page' : '';

        return sprintf('%s (/%s) - %s%s', $page->title, ltrim((string) $page->slug, '/'), $status, $current);
    }

    private static function pageHasRelatedListingSource(?Page $page): bool
    {
        return (bool) $page?->getKey()
            && ($page->childPages()->exists() || $page->fileDocuments()->exists());
    }

    private static function scheduleFields(bool $withScheduleFields): array
    {
        if (! $withScheduleFields) {
            return [];
        }

        return [
            self::hint(DateTimePicker::make('publish_at')
                ->label('Publish at'), 'Optional. Leave empty to show this block immediately.')
                ->helperText('Optional. Leave empty to show this block immediately.')
                ->columnSpan(1),
            self::hint(DateTimePicker::make('expires_at')
                ->label('Expire at'), 'Optional. Leave empty to keep this block visible indefinitely.')
                ->helperText('Optional. Leave empty to keep this block visible indefinitely.')
                ->afterOrEqual(fn (Get $get): ?string => $get('publish_at'))
                ->columnSpan(1),
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
        return SiteDesignPalette::backgroundOptions();
    }

    private static function textWidthOptions(): array
    {
        return [
            'small' => 'Small (600px)',
            'medium' => 'Medium (880px)',
            'wide' => 'Large (1180px)',
        ];
    }

    private static function contentWidthSelect(string $hint, string $default = 'medium'): Select
    {
        return self::hint(Select::make('content_width')
            ->label('Content width'), $hint)
            ->options(self::textWidthOptions())
            ->default($default)
            ->afterStateHydrated(function (Select $component, ?string $state) use ($default): void {
                if (blank($state) || $state === 'normal') {
                    $component->state($default);
                }
            })
            ->required()
            ->columnSpan(1);
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

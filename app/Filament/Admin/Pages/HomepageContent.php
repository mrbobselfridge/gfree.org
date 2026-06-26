<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\HasCentralizedAdminNavigation;
use App\Filament\Admin\Forms\ContentBlockBuilder;
use App\Filament\Admin\Pages\Concerns\RequiresAdminPageAccess;
use App\Filament\Admin\Support\AiPageReviewActions;
use App\Filament\Admin\Support\IconOnlyAction;
use App\Filament\Admin\Support\PublicPageActions;
use App\Filament\Admin\Support\WorkflowNotificationActions;
use App\Models\HomepageContent as HomepageContentModel;
use App\Models\SiteSetting;
use App\Models\WorkflowNotificationRule;
use App\Support\CodeBlockAccess;
use App\Support\SiteVariables;
use App\Support\WorkflowNotificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;

class HomepageContent extends Page
{
    use HasCentralizedAdminNavigation;
    use RequiresAdminPageAccess;

    protected static ?string $title = 'Homepage Content';

    protected static ?string $slug = 'homepage-content';

    public ?array $data = [];

    public HomepageContentModel $record;

    public function mount(): void
    {
        $this->record = HomepageContentModel::query()->firstOrCreate([], $this->defaultData());

        if (blank($this->record->content_blocks)) {
            $this->record->update($this->defaultData($this->record));
            $this->record->refresh();
        }

        $this->form->fill($this->record->attributesToArray());
    }

    public function save(): void
    {
        $this->persistRecord();

        Notification::make()
            ->success()
            ->title('Saved')
            ->duration(10000)
            ->actions([
                PublicPageActions::notificationAction(route('home')),
            ])
            ->send();
    }

    public function saveForAiPageReview(): void
    {
        $this->persistRecord(sendWorkflowNotifications: false);
    }

    private function persistRecord(bool $sendWorkflowNotifications = true): void
    {
        $data = $this->form->getState();
        $data['content_blocks'] = CodeBlockAccess::protectBlocks(
            $this->removeLegacyAnnouncementBlocks($data['content_blocks'] ?? []),
            $this->record->content_blocks,
        );

        $this->record->update($data);

        if ($sendWorkflowNotifications) {
            app(WorkflowNotificationService::class)->automaticForRecord(
                $this->record,
                WorkflowNotificationRule::TRIGGER_UPDATED,
            );
        }
    }

    private function removeLegacyAnnouncementBlocks(array $blocks): array
    {
        return collect($blocks)
            ->reject(fn (array $block): bool => ($block['type'] ?? null) === 'announcements_bar')
            ->values()
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            PublicPageActions::button('viewPublicPage', route('home')),
            AiPageReviewActions::make($this->record, fn (): mixed => $this->saveForAiPageReview()),
            ...WorkflowNotificationActions::notifyTeamForRecordActions($this->record),
            IconOnlyAction::make(
                Action::make('save')
                    ->label('Save')
                    ->action('save')
                    ->color('success'),
                Heroicon::OutlinedCheck,
            ),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->model($this->record)
            ->operation('edit')
            ->statePath('data')
            ->columns(3)
            ->components([
                Section::make('Homepage Hero')
                    ->description('Controls the public homepage hero banner rotation.')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->schema([
                        ToggleButtons::make('hero_banners_auto_rotate')
                            ->label('Auto-rotate hero banners')
                            ->boolean()
                            ->inline()
                            ->default(false)
                            ->required()
                            ->hintIcon(
                                Heroicon::OutlinedInformationCircle,
                                'When enabled, multiple live homepage banners fade from one to the next using the timing settings below. Visitors can pause the rotation on the public page.'
                            )
                            ->hintColor('gray'),
                        TextInput::make('hero_banners_rotation_delay_seconds')
                            ->label('Rotation delay seconds')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(300)
                            ->default(HomepageContentModel::DEFAULT_HERO_BANNERS_ROTATION_DELAY_SECONDS)
                            ->required()
                            ->helperText('Seconds to wait before moving to the next homepage banner when auto-rotate is enabled.'),
                        TextInput::make('hero_banners_fade_duration_seconds')
                            ->label('Fade duration seconds')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(30)
                            ->default(HomepageContentModel::DEFAULT_HERO_BANNERS_FADE_DURATION_SECONDS)
                            ->required()
                            ->helperText('Seconds used for the fade-out/fade-in transition between homepage banners.'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Homepage Content Blocks')
                    ->description('Build the homepage body here. These sections appear after the Sunday details and before Latest at TwyxtCo.')
                    ->icon(Heroicon::OutlinedRectangleGroup)
                    ->iconColor('success')
                    ->extraAttributes([
                        'class' => 'rounded-xl border border-success-500/30 bg-success-50/40 p-6 dark:bg-success-950/10',
                    ])
                    ->schema([
                        ContentBlockBuilder::make('content_blocks', 'homepage/content-images', withScheduleFields: true, withPageBlocks: true),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Homepage SEO')
                    ->description('Controls the public homepage title and description used by browsers, search engines, and analytics.')
                    ->icon(Heroicon::OutlinedMagnifyingGlass)
                    ->schema([
                        TextInput::make('seo_title')
                            ->label('SEO Page Title')
                            ->helperText('Defaults to the church name from Site Settings when blank.')
                            ->maxLength(255),
                        Textarea::make('seo_description')
                            ->label('SEO Page Description')
                            ->helperText('Defaults to the site tagline, then the active banner subtitle, when blank.')
                            ->rows(1)
                            ->columnSpan(2),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    IconOnlyAction::make(
                        Action::make('save')
                            ->label('Save')
                            ->submit('save')
                            ->color('success')
                            ->keyBindings(['mod+s', 'mod+enter', 'ctrl+enter']),
                        Heroicon::OutlinedCheck,
                    ),
                    PublicPageActions::button('viewPublicPageFooter', route('home')),
                    ...WorkflowNotificationActions::notifyTeamForRecordActions($this->record),
                ])
                    ->alignment(Alignment::Start)
                    ->key('form-actions'),
            ]);
    }

    private function defaultData(?HomepageContentModel $record = null): array
    {
        $defaults = config('twyxtco.homepage');
        $featureUrl = $defaults['feature']['url'] ?? null;
        $settings = SiteSetting::query()->first();

        return [
            'seo_title' => $record?->seo_title,
            'seo_description' => $record?->seo_description,
            'hero_banners_auto_rotate' => $record?->hero_banners_auto_rotate ?? false,
            'hero_banners_rotation_delay_seconds' => $record?->heroBannersRotationDelaySeconds() ?? HomepageContentModel::DEFAULT_HERO_BANNERS_ROTATION_DELAY_SECONDS,
            'hero_banners_fade_duration_seconds' => $record?->heroBannersFadeDurationSeconds() ?? HomepageContentModel::DEFAULT_HERO_BANNERS_FADE_DURATION_SECONDS,
            'content_blocks' => [
                [
                    'type' => 'info_strip',
                    'data' => [
                        'spacing' => 'bottom',
                        'content_width' => 'wide',
                        'background' => 'white',
                        'background_target' => 'item',
                        'items' => collect($defaults['service_details'] ?? [])
                            ->map(fn (array $detail, int $index): array => [
                                'label' => $detail['label'] ?? null,
                                'value' => match ($index) {
                                    0 => SiteVariables::variableValue('service-times', $settings) !== null ? '[[service-times]]' : ($detail['value'] ?? null),
                                    1 => SiteVariables::variableValue('address', $settings) !== null ? '[[address]]' : ($detail['value'] ?? null),
                                    default => $detail['value'] ?? null,
                                },
                            ])
                            ->all(),
                    ],
                ],
                [
                    'type' => 'text',
                    'data' => [
                        'eyebrow' => $record?->intro_eyebrow ?? $defaults['intro']['eyebrow'] ?? null,
                        'heading' => $record?->intro_title ?? $defaults['intro']['title'] ?? null,
                        'body' => $record?->intro_body ?? '<p>'.($defaults['intro']['body'] ?? '').'</p>',
                        'background' => 'white',
                    ],
                ],
                [
                    'type' => 'link_cards',
                    'data' => [
                        'eyebrow' => 'Serving',
                        'heading' => 'Start with a clear next step.',
                        'background' => 'black',
                        'background_target' => 'page',
                        'cards' => collect($defaults['next_steps'] ?? [])->map(fn (array $step): array => [
                            'title' => $step['title'] ?? '',
                            'summary' => $step['summary'] ?? null,
                            'url' => $step['url'] ?? '#',
                        ])->all(),
                    ],
                ],
                [
                    'type' => 'process_steps',
                    'data' => [
                        'eyebrow' => $record?->process_eyebrow ?? $defaults['process']['eyebrow'] ?? null,
                        'heading' => $record?->process_title ?? $defaults['process']['title'] ?? null,
                        'background' => 'white',
                        'background_target' => 'page',
                        'steps' => $record?->process_steps ?? $defaults['process']['steps'] ?? [],
                    ],
                ],
                [
                    'type' => 'image_text',
                    'data' => [
                        'eyebrow' => $record?->feature_eyebrow ?? $defaults['feature']['eyebrow'] ?? null,
                        'heading' => $record?->feature_title ?? $defaults['feature']['title'] ?? null,
                        'body' => $record?->feature_body ?? '<p>'.($defaults['feature']['body'] ?? '').'</p>',
                        'button_label' => $record?->feature_label ?? $defaults['feature']['label'] ?? null,
                        'button_url' => ($record?->feature_url ?? $featureUrl) === '#' ? null : ($record?->feature_url ?? $featureUrl),
                        'background' => 'forest',
                        'image_position' => 'right',
                    ],
                ],
            ],
        ];
    }
}

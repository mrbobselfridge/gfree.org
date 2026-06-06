<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Forms\ContentBlockBuilder;
use App\Filament\Admin\Pages\Concerns\RequiresAdminPageAccess;
use App\Filament\Admin\Support\IconOnlyAction;
use App\Filament\Admin\Support\PublicPageActions;
use App\Filament\Admin\Support\WorkflowNotificationActions;
use App\Models\HomepageContent as HomepageContentModel;
use App\Models\SiteSetting;
use App\Models\WorkflowNotificationRule;
use App\Support\WorkflowNotificationService;
use BackedEnum;
use Filament\Actions\Action;
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
    use RequiresAdminPageAccess;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Homepage';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Homepage Content';

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

        if (! $this->hasAnnouncementsBar($this->record->content_blocks)) {
            $this->record->update([
                'content_blocks' => [
                    ...$this->record->content_blocks,
                    $this->defaultAnnouncementsBarBlock(),
                ],
            ]);
            $this->record->refresh();
        }

        $this->form->fill($this->record->attributesToArray());
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $data['content_blocks'] = $this->normalizeContentBlocks($data['content_blocks'] ?? []);

        $this->record->update($data);

        app(WorkflowNotificationService::class)->automaticForRecord(
            $this->record,
            WorkflowNotificationRule::TRIGGER_UPDATED,
        );

        Notification::make()
            ->success()
            ->title('Saved')
            ->duration(10000)
            ->actions([
                PublicPageActions::notificationAction(route('home')),
            ])
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            PublicPageActions::button('viewPublicPage', route('home')),
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
            ->components([
                Section::make('Homepage Content Blocks')
                    ->description('Build the homepage body here. These sections appear after the Sunday details and before Latest at TwyxtCo.')
                    ->icon(Heroicon::OutlinedRectangleGroup)
                    ->iconColor('success')
                    ->extraAttributes([
                        'class' => 'rounded-xl border border-success-500/30 bg-success-50/40 p-6 dark:bg-success-950/10',
                    ])
                    ->schema([
                        ContentBlockBuilder::make('content_blocks', 'homepage/content-images'),
                    ])
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
        $oneChurchUrl = SiteSetting::query()->value('one_church_url');

        if ($oneChurchUrl && (blank($featureUrl) || $featureUrl === '#')) {
            $featureUrl = $oneChurchUrl;
        }

        return [
            'content_blocks' => [
                [
                    'type' => 'info_strip',
                    'data' => [
                        'spacing' => 'bottom',
                        'items' => collect($defaults['service_details'] ?? [])
                            ->map(fn (array $detail, int $index): array => [
                                'label' => $detail['label'] ?? null,
                                'source' => match ($index) {
                                    0 => 'sunday_service_times',
                                    1 => 'address',
                                    default => 'custom',
                                },
                                'value' => $detail['value'] ?? null,
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
                $this->defaultAnnouncementsBarBlock(),
            ],
        ];
    }

    private function defaultAnnouncementsBarBlock(): array
    {
        return [
            'type' => 'announcements_bar',
            'data' => [
                'is_visible' => true,
                'heading' => 'Latest at TwyxtCo',
                'link_label' => 'View all',
                'link_url' => '/announcements',
                'background' => 'white',
            ],
        ];
    }

    private function hasAnnouncementsBar(?array $blocks): bool
    {
        return collect($blocks)
            ->contains(fn (array $block): bool => ($block['type'] ?? null) === 'announcements_bar');
    }

    private function normalizeContentBlocks(array $blocks): array
    {
        $hasAnnouncementsBar = false;

        return collect($blocks)
            ->filter(function (array $block) use (&$hasAnnouncementsBar): bool {
                if (($block['type'] ?? null) !== 'announcements_bar') {
                    return true;
                }

                if ($hasAnnouncementsBar) {
                    return false;
                }

                $hasAnnouncementsBar = true;

                return true;
            })
            ->values()
            ->all();
    }
}

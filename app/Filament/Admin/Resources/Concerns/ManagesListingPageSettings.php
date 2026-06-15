<?php

namespace App\Filament\Admin\Resources\Concerns;

use App\Filament\Admin\Forms\ImageUpload;
use App\Filament\Admin\Forms\RichEditorDefaults;
use App\Filament\Admin\Support\IconOnlyAction;
use App\Filament\Admin\Support\PublicPageActions;
use App\Models\SiteSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

trait ManagesListingPageSettings
{
    public ?array $listingSettingsData = [];

    public SiteSetting $listingSettingsRecord;

    abstract protected function getListingSettingsPrefix(): string;

    abstract protected function getListingSettingsLabelPrefix(): string;

    protected function mountListingSettings(): void
    {
        $this->listingSettingsRecord = SiteSetting::query()->firstOrCreate([], [
            'church_name' => 'TwyxtCo Church',
        ]);

        $this->getSchema('listingSettingsForm')?->fill(
            $this->listingSettingsRecord->only($this->getListingSettingsFieldNames()),
        );
    }

    public function saveListingSettings(): void
    {
        $this->listingSettingsRecord->update($this->getSchema('listingSettingsForm')?->getState() ?? []);

        $notification = Notification::make()
            ->success()
            ->title($this->getListingSettingsSavedNotificationTitle());

        $notification = PublicPageActions::withNotificationAction($notification, $this->getListingSettingsPublicUrl());

        $notification->send();
    }

    public function listingSettingsForm(Schema $schema): Schema
    {
        return $schema
            ->model($this->listingSettingsRecord)
            ->operation('edit')
            ->statePath('listingSettingsData')
            ->components([
                Section::make($this->getListingSettingsSectionHeading())
                    ->icon(Heroicon::OutlinedPhoto)
                    ->afterHeader([
                        $this->getListingSettingsToggleControl(),
                    ])
                    ->schema([
                        ...$this->getListingSettingsFields(),
                        $this->getListingSettingsSaveAction(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed($this->isListingSettingsCollapsedByDefault())
                    ->persistCollapsed($this->shouldPersistListingSettingsCollapsedState()),
            ]);
    }

    protected function getListingSettingsContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('listingSettingsForm')])
            ->id('listing-settings-form')
            ->livewireSubmitHandler('saveListingSettings');
    }

    protected function getListingSettingsSectionHeading(): string
    {
        return "{$this->getListingSettingsLabelPrefix()} Landing Page Content";
    }

    protected function getListingSettingsSavedNotificationTitle(): string
    {
        return "{$this->getListingSettingsLabelPrefix()} landing page saved";
    }

    protected function isListingSettingsCollapsedByDefault(): bool
    {
        return true;
    }

    protected function shouldPersistListingSettingsCollapsedState(): bool
    {
        return true;
    }

    protected function getListingSettingsImageDirectory(): string
    {
        return $this->getListingSettingsPrefix();
    }

    protected function getListingSettingsFieldLabelPrefix(): string
    {
        return $this->getListingSettingsLabelPrefix();
    }

    protected function getListingSettingsFieldNames(): array
    {
        $prefix = $this->getListingSettingsPrefix();

        return [
            "{$prefix}_small_label",
            "{$prefix}_title",
            "{$prefix}_subtitle",
            "{$prefix}_image_path",
        ];
    }

    protected function getListingSettingsFields(): array
    {
        $prefix = $this->getListingSettingsPrefix();
        $labelPrefix = $this->getListingSettingsFieldLabelPrefix();

        return [
            TextInput::make("{$prefix}_small_label")
                ->label("{$labelPrefix} small label")
                ->maxLength(255),
            TextInput::make("{$prefix}_title")
                ->label("{$labelPrefix} title")
                ->maxLength(255),
            RichEditorDefaults::configure(RichEditor::make("{$prefix}_subtitle"))
                ->label("{$labelPrefix} subtitle"),
            ...ImageUpload::make("{$prefix}_image_path", 'site-settings/'.$this->getListingSettingsImageDirectory(), "{$labelPrefix} image"),
        ];
    }

    private function getListingSettingsToggleControl(): Html
    {
        return Html::make(new HtmlString(<<<'HTML'
            <button
                type="button"
                x-on:click.stop="isCollapsed = ! isCollapsed"
                class="text-sm font-medium text-gray-500 transition hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <span x-show="isCollapsed">Expand</span>
                <span x-show="! isCollapsed" x-cloak>Collapse</span>
            </button>
        HTML));
    }

    private function getListingSettingsSaveAction(): Actions
    {
        return Actions::make([
            IconOnlyAction::make(
                Action::make('saveListingSettings')
                    ->label('Save Landing Page Settings')
                    ->submit('saveListingSettings')
                    ->color('success')
                    ->keyBindings(['mod+s', 'mod+enter', 'ctrl+enter']),
                Heroicon::OutlinedCheck,
            ),
            ...$this->getListingSettingsViewPublicPageActions(),
        ])
            ->alignment(Alignment::Start)
            ->key('listing-settings-actions')
            ->columnSpanFull();
    }

    protected function getListingSettingsPublicUrl(): ?string
    {
        return match ($this->getListingSettingsPrefix()) {
            'ministry' => route('ministries.index'),
            default => null,
        };
    }

    protected function getListingSettingsViewPublicPageActions(): array
    {
        $action = PublicPageActions::button(
            'viewPublicListingPage',
            $this->getListingSettingsPublicUrl(),
        );

        return $action ? [$action] : [];
    }
}

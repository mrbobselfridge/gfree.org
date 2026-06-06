<?php

namespace App\Filament\Admin\Resources\Concerns;

use App\Filament\Admin\Support\IconOnlyAction;
use App\Filament\Admin\Support\PublicPageActions;
use App\Models\WorkflowNotificationRule;
use App\Support\PublicPageUrls;
use App\Support\WorkflowNotificationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

trait UsesStandardCreateActions
{
    protected function getHeaderActions(): array
    {
        return [
            $this->getHeaderCancelAction(),
            $this->getHeaderCreateAnotherAction(),
            $this->getHeaderCreateAction(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            ...($this->canCreateAnother() ? [$this->getCreateAnotherFormAction()] : []),
            $this->getCancelFormAction(),
        ];
    }

    protected function getCreateFormAction(): Action
    {
        return IconOnlyAction::make(
            parent::getCreateFormAction()
                ->label('Create')
                ->color('success'),
            Heroicon::OutlinedPlus,
        );
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return IconOnlyAction::make(
            parent::getCreateAnotherFormAction()
                ->label('Create & add another')
                ->color('success'),
            Heroicon::OutlinedSquaresPlus,
        );
    }

    protected function getCancelFormAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('cancel')
                ->label('Cancel')
                ->url($this->getResourceUrl())
                ->color('primary'),
            Heroicon::OutlinedXMark,
        );
    }

    protected function getHeaderCancelAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('headerCancel')
                ->label('Cancel')
                ->url($this->getResourceUrl())
                ->color('primary'),
            Heroicon::OutlinedXMark,
        );
    }

    protected function getHeaderCreateAnotherAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('headerCreateAnother')
                ->label('Create & add another')
                ->action('createAnother')
                ->color('success'),
            Heroicon::OutlinedSquaresPlus,
        );
    }

    protected function getHeaderCreateAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('headerCreate')
                ->label('Create')
                ->action('create')
                ->color('success'),
            Heroicon::OutlinedPlus,
        );
    }

    public function createAnother(): void
    {
        $this->create(another: true);

        $this->dispatch('twyxtco-focus-first-form-field');
    }

    protected function afterCreate(): void
    {
        app(WorkflowNotificationService::class)->automaticForRecord(
            $this->getRecord(),
            WorkflowNotificationRule::TRIGGER_CREATED,
        );
    }

    protected function getCreatedNotification(): ?Notification
    {
        $notification = parent::getCreatedNotification();

        if (! $notification) {
            return null;
        }

        return PublicPageActions::withNotificationAction(
            $notification,
            PublicPageUrls::forRecord($this->getRecord()),
        );
    }

    protected function getRedirectUrl(): string
    {
        $resource = static::getResource();

        if ($resource::hasPage('edit') && $resource::canEdit($this->getRecord())) {
            return $this->getResourceUrl('edit', $this->getRedirectUrlParameters());
        }

        return parent::getRedirectUrl();
    }
}

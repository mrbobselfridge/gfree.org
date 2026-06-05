<?php

namespace App\Filament\Admin\Resources\Concerns;

use App\Filament\Admin\Support\PublicPageActions;
use App\Filament\Admin\Support\WorkflowNotificationActions;
use App\Models\WorkflowNotificationRule;
use App\Support\PublicPageUrls;
use App\Support\WorkflowNotificationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;

trait UsesStandardEditActions
{
    protected function getHeaderActions(): array
    {
        return [
            $this->getHeaderCancelAction(),
            ...$this->getHeaderViewPublicPageActions(),
            ...WorkflowNotificationActions::notifyTeamForRecordActions($this->getRecord()),
            $this->getHeaderDeleteAction(),
            $this->getHeaderSaveAndCloseAction(),
            $this->getHeaderSaveAction(),
        ];
    }

    public function saveAndClose(): void
    {
        $this->save(shouldRedirect: false);

        $this->redirect($this->getResourceUrl());
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getSaveAndCloseFormAction(),
            $this->getDeleteFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Save')
            ->color('success')
            ->keyBindings(['mod+s', 'mod+enter', 'ctrl+enter']);
    }

    protected function getSaveAndCloseFormAction(): Action
    {
        return Action::make('saveAndClose')
            ->label('Save & close')
            ->action('saveAndClose')
            ->color('success');
    }

    protected function getDeleteFormAction(): DeleteAction
    {
        return DeleteAction::make('deleteFromForm')
            ->label('Delete')
            ->after(fn (): mixed => app(WorkflowNotificationService::class)->automaticForRecord(
                $this->getRecord(),
                WorkflowNotificationRule::TRIGGER_DELETED,
            ));
    }

    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancel')
            ->url($this->getResourceUrl())
            ->color('primary');
    }

    protected function getHeaderCancelAction(): Action
    {
        return Action::make('headerCancel')
            ->label('Cancel')
            ->url($this->getResourceUrl())
            ->color('primary');
    }

    protected function getHeaderDeleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->after(fn (): mixed => app(WorkflowNotificationService::class)->automaticForRecord(
                $this->getRecord(),
                WorkflowNotificationRule::TRIGGER_DELETED,
            ));
    }

    protected function getHeaderSaveAndCloseAction(): Action
    {
        return Action::make('headerSaveAndClose')
            ->label('Save & close')
            ->action('saveAndClose')
            ->color('success');
    }

    protected function getHeaderSaveAction(): Action
    {
        return Action::make('headerSave')
            ->label('Save')
            ->action('save')
            ->color('success');
    }

    protected function afterSave(): void
    {
        app(WorkflowNotificationService::class)->automaticForRecord(
            $this->getRecord(),
            WorkflowNotificationRule::TRIGGER_UPDATED,
        );
    }

    protected function getHeaderViewPublicPageActions(): array
    {
        $action = PublicPageActions::button('headerViewPublicPage', $this->getPublicPageUrl());

        return $action ? [$action] : [];
    }

    protected function getPublicPageUrl(): ?string
    {
        return PublicPageUrls::forRecord($this->getRecord());
    }

    protected function getSavedNotification(): ?Notification
    {
        $notification = parent::getSavedNotification();

        if (! $notification) {
            return null;
        }

        return PublicPageActions::withNotificationAction($notification, $this->getPublicPageUrl());
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }
}

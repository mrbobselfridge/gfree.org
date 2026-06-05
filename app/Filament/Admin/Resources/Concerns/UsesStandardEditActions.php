<?php

namespace App\Filament\Admin\Resources\Concerns;

use App\Filament\Admin\Support\PublicPageActions;
use App\Support\PublicPageUrls;
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
            DeleteAction::make(),
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
            ->label('Delete');
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

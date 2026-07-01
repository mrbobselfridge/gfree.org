<?php

namespace App\Filament\Admin\Resources\Concerns;

use App\Filament\Admin\Support\AiPageReviewActions;
use App\Filament\Admin\Support\IconOnlyAction;
use App\Filament\Admin\Support\PublicPageActions;
use App\Filament\Admin\Support\WorkflowNotificationActions;
use App\Models\WorkflowNotificationRule;
use App\Support\CodeBlockAccess;
use App\Support\PublicPageUrls;
use App\Support\WorkflowNotificationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

trait UsesStandardEditActions
{
    protected function getHeaderActions(): array
    {
        return [
            $this->getHeaderCancelAction(),
            ...$this->getHeaderViewPublicPageActions(),
            ...$this->getHeaderAiPageReviewActions(),
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
        return IconOnlyAction::make(
            parent::getSaveFormAction()
                ->label('Save')
                ->color('success')
                ->keyBindings(['mod+s']),
            Heroicon::OutlinedCheck,
        );
    }

    protected function getSaveAndCloseFormAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('saveAndClose')
                ->label('Save & close')
                ->action('saveAndClose')
                ->color('success')
                ->keyBindings(['mod+enter', 'ctrl+enter']),
            Heroicon::OutlinedDocumentCheck,
        );
    }

    protected function getDeleteFormAction(): DeleteAction
    {
        return IconOnlyAction::make(
            DeleteAction::make('deleteFromForm')
                ->label('Delete')
                ->before(fn (): mixed => app(WorkflowNotificationService::class)->prepareDeletedRecordSnapshot($this->getRecord()))
                ->after(fn (): mixed => app(WorkflowNotificationService::class)->automaticForRecord(
                    $this->getRecord(),
                    WorkflowNotificationRule::TRIGGER_DELETED,
                )),
            Heroicon::OutlinedTrash,
        );
    }

    protected function getCancelFormAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('cancel')
                ->label('Cancel')
                ->url($this->getResourceUrl())
                ->color('primary')
                ->extraAttributes(['data-twyxtco-admin-shortcut' => 'cancel'], merge: true),
            Heroicon::OutlinedXMark,
            'Cancel (Esc)',
        );
    }

    protected function getHeaderCancelAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('headerCancel')
                ->label('Cancel')
                ->url($this->getResourceUrl())
                ->color('primary')
                ->extraAttributes(['data-twyxtco-admin-shortcut' => 'cancel'], merge: true),
            Heroicon::OutlinedXMark,
            'Cancel (Esc)',
        );
    }

    protected function getHeaderDeleteAction(): DeleteAction
    {
        return IconOnlyAction::make(
            DeleteAction::make()
                ->before(fn (): mixed => app(WorkflowNotificationService::class)->prepareDeletedRecordSnapshot($this->getRecord()))
                ->after(fn (): mixed => app(WorkflowNotificationService::class)->automaticForRecord(
                    $this->getRecord(),
                    WorkflowNotificationRule::TRIGGER_DELETED,
                )),
            Heroicon::OutlinedTrash,
        );
    }

    protected function getHeaderSaveAndCloseAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('headerSaveAndClose')
                ->label('Save & close')
                ->action('saveAndClose')
                ->color('success'),
            Heroicon::OutlinedDocumentCheck,
            'Save & close (Ctrl/Cmd+Enter)',
        );
    }

    protected function getHeaderSaveAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('headerSave')
                ->label('Save')
                ->action('save')
                ->color('success'),
            Heroicon::OutlinedCheck,
            'Save (Ctrl/Cmd+S)',
        );
    }

    protected function afterSave(): void
    {
        app(WorkflowNotificationService::class)->automaticForRecord(
            $this->getRecord(),
            WorkflowNotificationRule::TRIGGER_UPDATED,
        );
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return CodeBlockAccess::protectFormData(
            $data,
            $this->getRecord()->content_blocks ?? null,
        );
    }

    protected function getHeaderViewPublicPageActions(): array
    {
        $action = PublicPageActions::button('headerViewPublicPage', $this->getPublicPageUrl());

        return $action ? [$action] : [];
    }

    protected function getHeaderAiPageReviewActions(): array
    {
        $action = AiPageReviewActions::make(
            $this->getRecord(),
            fn (): mixed => $this->save(shouldRedirect: false, shouldSendSavedNotification: false),
        );

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

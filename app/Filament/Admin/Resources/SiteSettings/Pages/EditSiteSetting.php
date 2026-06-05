<?php

namespace App\Filament\Admin\Resources\SiteSettings\Pages;

use App\Filament\Admin\Resources\SiteSettings\SiteSettingResource;
use App\Filament\Admin\Support\WorkflowNotificationActions;
use App\Models\WorkflowNotificationRule;
use App\Support\WorkflowNotificationService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditSiteSetting extends EditRecord
{
    protected static string $resource = SiteSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getCancelHeaderAction(),
            ...WorkflowNotificationActions::notifyTeamForRecordActions($this->getRecord()),
            Action::make('save')
                ->label('Save')
                ->action('save')
                ->color('success'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
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

    protected function getCancelHeaderAction(): Action
    {
        return Action::make('cancelHeader')
            ->label('Cancel')
            ->url(SiteSettingResource::getUrl('index'))
            ->color('gray');
    }

    protected function getCancelFormAction(): Action
    {
        return Action::make('cancelForm')
            ->label('Cancel')
            ->url(SiteSettingResource::getUrl('index'))
            ->color('gray');
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }

    protected function afterSave(): void
    {
        app(WorkflowNotificationService::class)->automaticForRecord(
            $this->getRecord(),
            WorkflowNotificationRule::TRIGGER_UPDATED,
        );
    }
}

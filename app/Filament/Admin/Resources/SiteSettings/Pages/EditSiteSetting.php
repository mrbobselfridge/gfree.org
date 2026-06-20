<?php

namespace App\Filament\Admin\Resources\SiteSettings\Pages;

use App\Filament\Admin\Resources\SiteSettings\SiteSettingResource;
use App\Filament\Admin\Support\IconOnlyAction;
use App\Filament\Admin\Support\WorkflowNotificationActions;
use App\Models\WorkflowNotificationRule;
use App\Support\CodeBlockAccess;
use App\Support\WorkflowNotificationService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditSiteSetting extends EditRecord
{
    protected static string $resource = SiteSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getCancelHeaderAction(),
            ...WorkflowNotificationActions::notifyTeamForRecordActions($this->getRecord()),
            IconOnlyAction::make(
                Action::make('save')
                    ->label('Save')
                    ->action('save')
                    ->color('success'),
                Heroicon::OutlinedCheck,
            ),
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
        return IconOnlyAction::make(
            parent::getSaveFormAction()
                ->label('Save')
                ->color('success')
                ->keyBindings(['mod+s', 'mod+enter', 'ctrl+enter']),
            Heroicon::OutlinedCheck,
        );
    }

    protected function getCancelHeaderAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('cancelHeader')
                ->label('Cancel')
                ->url(SiteSettingResource::getUrl('index'))
                ->color('gray'),
            Heroicon::OutlinedXMark,
        );
    }

    protected function getCancelFormAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('cancelForm')
                ->label('Cancel')
                ->url(SiteSettingResource::getUrl('index'))
                ->color('gray'),
            Heroicon::OutlinedXMark,
        );
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! CodeBlockAccess::canManage()) {
            $data['custom_css'] = $this->getRecord()->custom_css;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        app(WorkflowNotificationService::class)->automaticForRecord(
            $this->getRecord(),
            WorkflowNotificationRule::TRIGGER_UPDATED,
        );
    }
}

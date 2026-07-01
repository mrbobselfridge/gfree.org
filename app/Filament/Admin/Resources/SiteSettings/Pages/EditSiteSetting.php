<?php

namespace App\Filament\Admin\Resources\SiteSettings\Pages;

use App\Filament\Admin\Resources\SiteSettings\SiteSettingResource;
use App\Filament\Admin\Support\IconOnlyAction;
use App\Filament\Admin\Support\WorkflowNotificationActions;
use App\Models\WorkflowNotificationRule;
use App\Support\CodeBlockAccess;
use App\Support\SiteVariables;
use App\Support\WorkflowNotificationService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

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
                ->keyBindings(['mod+s']),
            Heroicon::OutlinedCheck,
        );
    }

    protected function getCancelHeaderAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('cancelHeader')
                ->label('Cancel')
                ->url(SiteSettingResource::getUrl('index'))
                ->color('gray')
                ->extraAttributes(['data-twyxtco-admin-shortcut' => 'cancel'], merge: true),
            Heroicon::OutlinedXMark,
        );
    }

    protected function getCancelFormAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('cancelForm')
                ->label('Cancel')
                ->url(SiteSettingResource::getUrl('index'))
                ->color('gray')
                ->extraAttributes(['data-twyxtco-admin-shortcut' => 'cancel'], merge: true),
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
            $data['header_custom_js'] = $this->getRecord()->header_custom_js;
            $data['body_top_custom_js'] = $this->getRecord()->body_top_custom_js;
            $data['body_bottom_custom_js'] = $this->getRecord()->body_bottom_custom_js;
            $data['site_variables'] = $this->getRecord()->site_variables;
        } else {
            $data['site_variables'] = SiteVariables::normalizeRows($data['site_variables'] ?? []);
            $duplicates = collect($data['site_variables'])
                ->countBy('variable')
                ->filter(fn (int $count): bool => $count > 1)
                ->keys()
                ->all();

            if ($duplicates !== []) {
                throw ValidationException::withMessages([
                    'data.site_variables' => 'Each site variable must have a unique variable name. Duplicate: '.$duplicates[0].'.',
                ]);
            }
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

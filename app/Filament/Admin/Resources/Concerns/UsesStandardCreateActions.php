<?php

namespace App\Filament\Admin\Resources\Concerns;

use Filament\Actions\Action;

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
        return parent::getCreateFormAction()
            ->label('Create')
            ->color('success');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Create & add more')
            ->color('success');
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

    protected function getHeaderCreateAnotherAction(): Action
    {
        return Action::make('headerCreateAnother')
            ->label('Create & add more')
            ->action('createAnother')
            ->color('success');
    }

    protected function getHeaderCreateAction(): Action
    {
        return Action::make('headerCreate')
            ->label('Create')
            ->action('create')
            ->color('success');
    }

    public function createAnother(): void
    {
        $this->create(another: true);

        $this->dispatch('gfree-focus-first-form-field');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl();
    }
}

<?php

namespace App\Filament\Admin\Resources\Concerns;

use App\Filament\Admin\Support\IconOnlyAction;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;

trait UsesStandardListActions
{
    protected function getHeaderActions(): array
    {
        $resource = static::getResource();

        return [
            IconOnlyAction::make(
                CreateAction::make()
                    ->label('New '.$resource::getTitleCaseModelLabel())
                    ->color('success')
                    ->keyBindings(['alt+plus']),
                Heroicon::OutlinedPlus,
                'New '.$resource::getTitleCaseModelLabel().' (Alt++)',
            ),
        ];
    }
}

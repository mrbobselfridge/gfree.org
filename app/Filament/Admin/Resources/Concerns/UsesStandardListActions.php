<?php

namespace App\Filament\Admin\Resources\Concerns;

use Filament\Actions\CreateAction;

trait UsesStandardListActions
{
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->color('success'),
        ];
    }
}

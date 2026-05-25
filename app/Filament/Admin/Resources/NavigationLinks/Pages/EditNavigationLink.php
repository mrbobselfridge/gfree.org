<?php

namespace App\Filament\Admin\Resources\NavigationLinks\Pages;

use App\Filament\Admin\Resources\Concerns\RedirectsEditToIndex;
use App\Filament\Admin\Resources\NavigationLinks\NavigationLinkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNavigationLink extends EditRecord
{
    use RedirectsEditToIndex;

    protected static string $resource = NavigationLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

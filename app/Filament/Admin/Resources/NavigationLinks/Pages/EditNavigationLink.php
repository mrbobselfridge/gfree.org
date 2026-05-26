<?php

namespace App\Filament\Admin\Resources\NavigationLinks\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardEditActions;
use App\Filament\Admin\Resources\NavigationLinks\NavigationLinkResource;
use Filament\Resources\Pages\EditRecord;

class EditNavigationLink extends EditRecord
{
    use UsesStandardEditActions;

    protected static string $resource = NavigationLinkResource::class;
}

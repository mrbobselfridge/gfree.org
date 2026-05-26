<?php

namespace App\Filament\Admin\Resources\NavigationLinks\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardListActions;
use App\Filament\Admin\Resources\NavigationLinks\NavigationLinkResource;
use Filament\Resources\Pages\ListRecords;

class ListNavigationLinks extends ListRecords
{
    use UsesStandardListActions;

    protected static string $resource = NavigationLinkResource::class;
}

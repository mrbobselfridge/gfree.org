<?php

namespace App\Filament\Admin\Resources\NavigationLinks\Pages;

use App\Filament\Admin\Resources\Concerns\RedirectsCreateToIndex;
use App\Filament\Admin\Resources\NavigationLinks\NavigationLinkResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNavigationLink extends CreateRecord
{
    use RedirectsCreateToIndex;

    protected static string $resource = NavigationLinkResource::class;
}

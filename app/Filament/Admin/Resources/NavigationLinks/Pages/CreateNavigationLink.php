<?php

namespace App\Filament\Admin\Resources\NavigationLinks\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardCreateActions;
use App\Filament\Admin\Resources\NavigationLinks\NavigationLinkResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNavigationLink extends CreateRecord
{
    use UsesStandardCreateActions;

    protected static string $resource = NavigationLinkResource::class;
}

<?php

namespace App\Filament\Admin\Resources\SiteSettings\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardListActions;
use App\Filament\Admin\Resources\SiteSettings\SiteSettingResource;
use Filament\Resources\Pages\ListRecords;

class ListSiteSettings extends ListRecords
{
    use UsesStandardListActions;

    protected static string $resource = SiteSettingResource::class;
}

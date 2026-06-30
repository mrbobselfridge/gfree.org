<?php

namespace App\Filament\Admin\Resources\SiteAlerts\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardListActions;
use App\Filament\Admin\Resources\SiteAlerts\SiteAlertResource;
use Filament\Resources\Pages\ListRecords;

class ListSiteAlerts extends ListRecords
{
    use UsesStandardListActions;

    protected static string $resource = SiteAlertResource::class;
}

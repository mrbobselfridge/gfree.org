<?php

namespace App\Filament\Admin\Resources\SiteAlerts\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardCreateActions;
use App\Filament\Admin\Resources\SiteAlerts\SiteAlertResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSiteAlert extends CreateRecord
{
    use UsesStandardCreateActions;

    protected static string $resource = SiteAlertResource::class;
}

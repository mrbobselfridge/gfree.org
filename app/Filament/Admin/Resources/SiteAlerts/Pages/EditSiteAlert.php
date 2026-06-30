<?php

namespace App\Filament\Admin\Resources\SiteAlerts\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardEditActions;
use App\Filament\Admin\Resources\SiteAlerts\SiteAlertResource;
use Filament\Resources\Pages\EditRecord;

class EditSiteAlert extends EditRecord
{
    use UsesStandardEditActions;

    protected static string $resource = SiteAlertResource::class;
}

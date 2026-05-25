<?php

namespace App\Filament\Admin\Resources\SiteSettings\Pages;

use App\Filament\Admin\Resources\Concerns\RedirectsCreateToIndex;
use App\Filament\Admin\Resources\SiteSettings\SiteSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSiteSetting extends CreateRecord
{
    use RedirectsCreateToIndex;

    protected static string $resource = SiteSettingResource::class;
}

<?php

namespace App\Filament\Admin\Resources\SiteSettings\Pages;

use App\Filament\Admin\Resources\SiteSettings\SiteSettingResource;
use App\Models\SiteSetting;
use Filament\Resources\Pages\ListRecords;

class ListSiteSettings extends ListRecords
{
    protected static string $resource = SiteSettingResource::class;

    public function mount(): void
    {
        $record = SiteSetting::query()->firstOrCreate([], [
            'church_name' => 'gFree Church',
        ]);

        $this->redirect(SiteSettingResource::getUrl('edit', ['record' => $record]));
    }
}

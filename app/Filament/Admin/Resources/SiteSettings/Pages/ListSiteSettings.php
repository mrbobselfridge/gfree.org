<?php

namespace App\Filament\Admin\Resources\SiteSettings\Pages;

use App\Filament\Admin\Resources\SiteSettings\SiteSettingResource;
use App\Models\SiteSetting;
use App\Support\AiContentPrompt;
use Filament\Resources\Pages\ListRecords;

class ListSiteSettings extends ListRecords
{
    protected static string $resource = SiteSettingResource::class;

    public function mount(): void
    {
        $record = SiteSetting::query()->firstOrCreate([], [
            'church_name' => 'gFree Church',
            'ai_content_prompt' => AiContentPrompt::DEFAULT,
        ]);

        $this->redirect(SiteSettingResource::getUrl('edit', ['record' => $record]));
    }
}

<?php

namespace App\Filament\Admin\Resources\SiteSettings\Pages;

use App\Filament\Admin\Resources\SiteSettings\SiteSettingResource;
use App\Models\SiteSetting;
use App\Support\AiContentPrompt;
use App\Support\OpenAiSiteSettings;
use Filament\Resources\Pages\ListRecords;

class ListSiteSettings extends ListRecords
{
    protected static string $resource = SiteSettingResource::class;

    public function mount(): void
    {
        $record = SiteSetting::query()->firstOrCreate([], [
            'church_name' => 'gFree Church',
            'openai_bulletin_model' => OpenAiSiteSettings::DEFAULT_MODEL,
            'ai_content_prompt' => AiContentPrompt::DEFAULT,
        ]);

        $this->redirect(SiteSettingResource::getUrl('edit', ['record' => $record]));
    }
}

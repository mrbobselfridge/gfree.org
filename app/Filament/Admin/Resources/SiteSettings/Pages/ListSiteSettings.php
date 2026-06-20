<?php

namespace App\Filament\Admin\Resources\SiteSettings\Pages;

use App\Filament\Admin\Resources\SiteSettings\SiteSettingResource;
use App\Models\SiteSetting;
use App\Support\AiContentPrompt;
use App\Support\SiteDesignPalette;
use Filament\Resources\Pages\ListRecords;

class ListSiteSettings extends ListRecords
{
    protected static string $resource = SiteSettingResource::class;

    public function mount(): void
    {
        $record = SiteSetting::query()->firstOrCreate([], [
            'church_name' => 'TwyxtCo Church',
            'ai_content_prompt' => AiContentPrompt::DEFAULT,
            'design_background_colors' => SiteDesignPalette::defaultBackgroundColors(),
            'design_accent_color' => SiteSetting::DEFAULT_DESIGN_ACCENT_COLOR,
            'design_accent_text_color' => SiteSetting::DEFAULT_DESIGN_ACCENT_TEXT_COLOR,
            'design_accent_soft_color' => SiteSetting::DEFAULT_DESIGN_ACCENT_SOFT_COLOR,
        ]);

        $this->redirect(SiteSettingResource::getUrl('edit', ['record' => $record]));
    }
}

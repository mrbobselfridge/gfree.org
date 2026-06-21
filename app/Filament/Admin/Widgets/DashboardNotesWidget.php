<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\SiteSettings\SiteSettingResource;
use App\Models\SiteSetting;
use App\Support\AdminAccess;
use App\Support\RichContent;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Str;

class DashboardNotesWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.dashboard-notes-widget';

    protected int|string|array $columnSpan = 1;

    protected static bool $isLazy = false;

    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return RichContent::hasRenderableContent(SiteSetting::query()->value('dashboard_notes'));
    }

    protected function getViewData(): array
    {
        $settings = SiteSetting::query()->first();
        $notes = RichContent::render($settings?->dashboard_notes);
        $canEditNotes = AdminAccess::canAccessTool(Filament::auth()->user(), AdminAccess::SITE_SETTINGS);

        return [
            'heading' => 'Dashboard notes',
            'description' => 'Links and notes from Site Settings.',
            'notesHtml' => $notes,
            'actionLabel' => $canEditNotes ? 'Edit notes' : null,
            'actionUrl' => ($canEditNotes && $settings)
                ? SiteSettingResource::getUrl('edit', ['record' => $settings])
                : null,
            'widgetKey' => Str::kebab(class_basename(static::class)),
        ];
    }
}

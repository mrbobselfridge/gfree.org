<?php

namespace App\Filament\Admin\Widgets;

use App\Models\AnalyticsPageView;
use App\Support\AdminAccess;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

abstract class AnalyticsDashboardWidget extends CmsDashboardWidget
{
    public static function canView(): bool
    {
        return AdminAccess::canAccessTool(Filament::auth()->user(), AdminAccess::SITE_SETTINGS);
    }

    protected function analyticsQuery(): Builder
    {
        return AnalyticsPageView::query();
    }
}

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
        $user = Filament::auth()->user();

        return AdminAccess::canAccessTool($user, AdminAccess::SITE_SETTINGS)
            || AdminAccess::canAccessTool($user, AdminAccess::ANALYTICS);
    }

    protected function analyticsQuery(): Builder
    {
        return AnalyticsPageView::query();
    }

    protected function countBadges(array $rows): array
    {
        return [];
    }
}

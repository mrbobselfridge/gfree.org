<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\SiteSettings\SiteSettingResource;
use App\Models\SiteSetting;
use App\Support\AdminAccess;
use App\Support\OpenAiUsageSummary;
use Filament\Facades\Filament;

class AiUsageWidget extends CmsDashboardWidget
{
    protected static ?int $sort = 52;

    public static function canView(): bool
    {
        return AdminAccess::canAccessTool(Filament::auth()->user(), AdminAccess::SITE_SETTINGS);
    }

    protected function heading(): string
    {
        return 'AI Usage';
    }

    protected function description(): ?string
    {
        return 'Current-month OpenAI spend for the configured app API key.';
    }

    protected function actionLabel(): ?string
    {
        return 'Open AI settings';
    }

    protected function actionUrl(): ?string
    {
        $settings = SiteSetting::query()->first();

        return $settings
            ? SiteSettingResource::getUrl('edit', ['record' => $settings])
            : SiteSettingResource::getUrl();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function rows(): array
    {
        $summary = app(OpenAiUsageSummary::class)->currentMonth();

        return [
            $this->row(
                type: 'OpenAI',
                title: $summary['status'] === 'ok' ? 'Current month usage spend' : $summary['title'],
                meta: $summary['body'],
                url: $this->actionUrl(),
                status: match ($summary['status']) {
                    'ok' => $summary['formatted_total'] ?? 'Loaded',
                    'missing' => 'Setup',
                    default => 'Error',
                },
                statusColor: match ($summary['status']) {
                    'ok' => 'success',
                    'missing' => 'warning',
                    default => 'danger',
                },
            ),
        ];
    }

    protected function countBadges(array $rows): array
    {
        return [];
    }
}

<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\Backups;
use App\Support\AdminAccess;
use App\Support\BackupProfiles;
use Filament\Facades\Filament;

class BackupStatusWidget extends CmsDashboardWidget
{
    protected static ?int $sort = 55;

    public static function canView(): bool
    {
        return AdminAccess::canAccessTool(Filament::auth()->user(), AdminAccess::BACKUPS);
    }

    protected function heading(): string
    {
        return 'Backups';
    }

    protected function description(): ?string
    {
        return 'Latest backup file for each configured backup profile.';
    }

    protected function emptyMessage(): string
    {
        return 'No backup profiles are configured.';
    }

    protected function actionLabel(): ?string
    {
        return 'Open backups';
    }

    protected function actionUrl(): ?string
    {
        return Backups::getUrl();
    }

    protected function rows(): array
    {
        return BackupProfiles::all()
            ->map(fn (array $profile): array => $this->profileRow($profile))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function profileRow(array $profile): array
    {
        $latest = BackupProfiles::latestBackup($profile['key']);

        if (! $latest) {
            return $this->row(
                type: 'Backup',
                title: $profile['label'],
                meta: $profile['schedule_label'],
                url: Backups::getUrl(),
                status: $profile['enabled'] ? 'Missing' : 'Manual',
                statusColor: $profile['enabled'] ? 'danger' : 'gray',
            );
        }

        return $this->row(
            type: 'Backup',
            title: $profile['label'],
            meta: collect([
                'Latest '.$latest['age'],
                $latest['size_for_humans'] ?? null,
                'Disk: '.$latest['disk'],
                $profile['schedule_label'],
            ])->filter()->implode(' | '),
            url: Backups::getUrl(),
            status: 'Available',
            statusColor: 'success',
        );
    }
}

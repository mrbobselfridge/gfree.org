<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Pages\Concerns\RequiresAdminPageAccess;
use App\Support\BackupProfiles;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;

class Backups extends Page
{
    use RequiresAdminPageAccess;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Site Tools';

    protected static ?int $navigationSort = 80;

    protected static ?string $navigationLabel = 'Backups';

    protected static ?string $title = 'Backups';

    protected static ?string $slug = 'backups';

    protected string $view = 'filament.admin.pages.backups';

    /**
     * @return Collection<string, array<string, mixed>>
     */
    public function profiles(): Collection
    {
        return BackupProfiles::all()
            ->map(function (array $profile): array {
                $profile['latest'] = BackupProfiles::latestBackup($profile['key']);
                $profile['recent_backups'] = BackupProfiles::backupRows($profile['key']);

                return $profile;
            });
    }

    protected function runBackupAction(): Action
    {
        return Action::make('runBackup')
            ->label('Run backup')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments): string => 'Run '.$this->profileLabel($arguments).' now?')
            ->modalDescription(fn (array $arguments): string => $this->profileDescription($arguments))
            ->modalSubmitActionLabel('Run backup')
            ->action(function (array $arguments): void {
                $profileKey = (string) ($arguments['profile'] ?? '');
                $profile = BackupProfiles::find($profileKey);

                if (! $profile) {
                    Notification::make()
                        ->title('Backup profile not found')
                        ->danger()
                        ->send();

                    return;
                }

                $result = BackupProfiles::run($profileKey);

                $notification = Notification::make()
                    ->title($result['exit_code'] === 0 ? 'Backup complete' : 'Backup failed')
                    ->body($this->notificationBody($result['output']));

                ($result['exit_code'] === 0 ? $notification->success() : $notification->danger())->send();
            });
    }

    protected function runMonitorAction(): Action
    {
        return Action::make('runMonitor')
            ->label('Check health')
            ->icon(Heroicon::OutlinedClock)
            ->action(function (): void {
                $exitCode = Artisan::call('backup:monitor');

                $notification = Notification::make()
                    ->title($exitCode === 0 ? 'Backups are healthy' : 'Backup health check found a problem')
                    ->body($this->notificationBody(Artisan::output()));

                ($exitCode === 0 ? $notification->success() : $notification->warning())->send();
            });
    }

    protected function deleteBackupAction(): Action
    {
        return Action::make('deleteBackup')
            ->label('Delete backup')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments): string => 'Delete '.$this->backupFilename($arguments).'?')
            ->modalDescription('This permanently removes the selected backup file from storage.')
            ->modalSubmitActionLabel('Delete backup')
            ->action(function (array $arguments): void {
                $deleted = BackupProfiles::deleteBackup(
                    profileKey: (string) ($arguments['profile'] ?? ''),
                    disk: (string) ($arguments['disk'] ?? ''),
                    encodedPath: (string) ($arguments['path'] ?? ''),
                );

                Notification::make()
                    ->title($deleted ? 'Backup deleted' : 'Backup could not be deleted')
                    ->body($deleted ? null : 'The backup file may have already been removed or could not be found.')
                    ->{$deleted ? 'success' : 'warning'}()
                    ->send();
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->runMonitorAction(),
        ];
    }

    private function profileLabel(array $arguments): string
    {
        return (string) (BackupProfiles::find((string) ($arguments['profile'] ?? ''))['label'] ?? 'backup');
    }

    private function profileDescription(array $arguments): string
    {
        $profile = BackupProfiles::find((string) ($arguments['profile'] ?? ''));

        if (! $profile) {
            return 'This backup profile could not be found.';
        }

        return "This will run the {$profile['type']} profile and may take a few minutes.";
    }

    private function backupFilename(array $arguments): string
    {
        $path = (string) ($arguments['name'] ?? '');

        return filled($path) ? $path : 'backup file';
    }

    private function notificationBody(string $output): ?string
    {
        $output = trim(strip_tags($output));

        return filled($output) ? str($output)->limit(500)->toString() : null;
    }
}

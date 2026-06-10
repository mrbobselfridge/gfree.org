<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Spatie\Backup\BackupDestination\BackupDestinationFactory;
use Spatie\Backup\Config\Config;

class BackupProfiles
{
    /**
     * @return Collection<string, array<string, mixed>>
     */
    public static function all(): Collection
    {
        return collect(config('backup_profiles.profiles', []))
            ->map(fn (array $profile, string $key): array => self::normalizeProfile($key, $profile))
            ->sortBy('sort');
    }

    public static function find(string $key): ?array
    {
        return self::all()->get($key);
    }

    public static function scheduleConfiguredProfiles(): void
    {
        self::all()
            ->filter(fn (array $profile): bool => (bool) $profile['enabled'])
            ->each(function (array $profile): void {
                $event = Schedule::command(self::command($profile['key']))
                    ->name('backup:'.$profile['key'])
                    ->description($profile['label'])
                    ->withoutOverlapping(240);

                self::applyFrequency($event, $profile);
            });

        Schedule::command('backup:clean --config=backup_database')
            ->dailyAt(env('BACKUP_CLEAN_DATABASE_TIME', '03:00'))
            ->name('backup:clean:database')
            ->withoutOverlapping(240);

        Schedule::command('backup:clean --config=backup_full')
            ->dailyAt(env('BACKUP_CLEAN_FULL_TIME', '03:20'))
            ->name('backup:clean:full')
            ->withoutOverlapping(240);

        Schedule::command('backup:clean --config=backup_archive')
            ->dailyAt(env('BACKUP_CLEAN_ARCHIVE_TIME', '03:40'))
            ->name('backup:clean:archive')
            ->withoutOverlapping(240);

        Schedule::command('backup:monitor')
            ->dailyAt(env('BACKUP_MONITOR_TIME', '04:00'))
            ->name('backup:monitor')
            ->withoutOverlapping(60);
    }

    public static function command(string $key): string
    {
        $profile = self::find($key);

        abort_unless($profile, 404);

        return collect([
            'backup:run',
            '--config='.$profile['config'],
            $profile['only'] === 'db' ? '--only-db' : null,
            $profile['only'] === 'files' ? '--only-files' : null,
        ])->filter()->implode(' ');
    }

    public static function run(string $key): array
    {
        $exitCode = Artisan::call(self::command($key));

        return [
            'exit_code' => $exitCode,
            'output' => trim(Artisan::output()),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function backupRows(string $key): Collection
    {
        $profile = self::find($key);

        if (! $profile) {
            return collect();
        }

        return self::destinations($profile)
            ->flatMap(function ($destination) use ($profile): Collection {
                if (! $destination->isReachable()) {
                    return collect([[
                        'profile' => $profile['key'],
                        'disk' => $destination->diskName(),
                        'path' => null,
                        'name' => null,
                        'date' => null,
                        'age' => 'Unavailable',
                        'size' => null,
                        'size_for_humans' => null,
                        'download_url' => null,
                    ]]);
                }

                return $destination->backups()
                    ->take(10)
                    ->map(fn ($backup): array => self::backupRow($profile['key'], $destination->diskName(), $backup->path(), $backup->date(), $backup->sizeInBytes()));
            })
            ->sortByDesc(fn (array $backup): int => $backup['date'] instanceof CarbonInterface ? $backup['date']->getTimestamp() : 0)
            ->values();
    }

    public static function latestBackup(string $key): ?array
    {
        return self::backupRows($key)
            ->first(fn (array $backup): bool => filled($backup['path']));
    }

    public static function downloadPath(string $profileKey, string $disk, string $encodedPath): ?string
    {
        $profile = self::find($profileKey);
        $path = self::decodePath($encodedPath);

        if (! $profile || ! $path || ! str_starts_with($path, self::backupName($profile).'/')) {
            return null;
        }

        return in_array($disk, self::diskNames($profile), true) && Storage::disk($disk)->exists($path)
            ? $path
            : null;
    }

    public static function backupName(array $profile): string
    {
        return (string) data_get(config($profile['config']), 'backup.name');
    }

    private static function backupRow(string $profile, string $disk, string $path, CarbonInterface $date, int|float $size): array
    {
        return [
            'profile' => $profile,
            'disk' => $disk,
            'path' => $path,
            'name' => basename($path),
            'date' => $date,
            'age' => $date->diffForHumans(),
            'size' => $size,
            'size_for_humans' => Number::fileSize($size),
            'download_url' => route('admin.backups.download', [
                'profile' => $profile,
                'disk' => $disk,
                'path' => self::encodePath($path),
            ]),
        ];
    }

    private static function applyFrequency(Event $event, array $profile): Event
    {
        $time = (string) $profile['time'];

        return match ($profile['frequency']) {
            'hourly' => $event->hourly(),
            'every_two_hours' => $event->everyTwoHours(),
            'every_three_hours' => $event->everyThreeHours(),
            'every_four_hours' => $event->everyFourHours(),
            'every_six_hours' => $event->everySixHours(),
            'every_twelve_hours' => $event->cron('0 */12 * * *'),
            'weekly' => $event->weeklyOn(self::dayNumber((string) $profile['day']), $time),
            'every_x_days' => $event
                ->dailyAt($time)
                ->when(fn (): bool => self::isIntervalDay((int) $profile['interval_days'])),
            default => $event->dailyAt($time),
        };
    }

    private static function isIntervalDay(int $intervalDays): bool
    {
        $intervalDays = max(1, $intervalDays);
        $start = Carbon::parse(env('BACKUP_INTERVAL_START_DATE', '2026-01-01'))->startOfDay();

        return $start->diffInDays(now()->startOfDay()) % $intervalDays === 0;
    }

    private static function dayNumber(string $day): int
    {
        return match (strtolower($day)) {
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            default => 0,
        };
    }

    private static function scheduleLabel(array $profile): string
    {
        $time = (string) $profile['time'];

        if (! $profile['enabled']) {
            return 'Manual only';
        }

        return match ($profile['frequency']) {
            'hourly' => 'Every hour',
            'every_two_hours' => 'Every 2 hours',
            'every_three_hours' => 'Every 3 hours',
            'every_four_hours' => 'Every 4 hours',
            'every_six_hours' => 'Every 6 hours',
            'every_twelve_hours' => 'Every 12 hours',
            'weekly' => 'Weekly on '.ucfirst((string) $profile['day']).' at '.$time,
            'every_x_days' => 'Every '.$profile['interval_days'].' days at '.$time,
            default => 'Daily at '.$time,
        };
    }

    private static function destinations(array $profile): Collection
    {
        return BackupDestinationFactory::createFromArray(
            Config::fromArray(config($profile['config']))
        );
    }

    private static function diskNames(array $profile): array
    {
        return (array) data_get(config($profile['config']), 'backup.destination.disks', []);
    }

    private static function normalizeProfile(string $key, array $profile): array
    {
        $profile['key'] = $key;
        $profile['sort'] ??= 999;
        $profile['enabled'] = (bool) ($profile['enabled'] ?? false);
        $profile['frequency'] ??= 'daily';
        $profile['time'] ??= '01:00';
        $profile['day'] ??= 'sunday';
        $profile['interval_days'] = max(1, (int) ($profile['interval_days'] ?? 1));
        $profile['schedule_label'] = self::scheduleLabel($profile);
        $profile['backup_name'] = self::backupName($profile);
        $profile['disks'] = self::diskNames($profile);

        return $profile;
    }

    private static function encodePath(string $path): string
    {
        return rtrim(strtr(base64_encode($path), '+/', '-_'), '=');
    }

    private static function decodePath(string $path): ?string
    {
        $decoded = base64_decode(strtr($path, '-_', '+/'), true);

        return is_string($decoded) && $decoded !== '' ? $decoded : null;
    }
}

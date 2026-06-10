<?php

use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

$config = require __DIR__.'/backup.php';

$config['backup']['name'] = env('BACKUP_ARCHIVE_NAME', env('APP_NAME', 'gfree-site').'-archive');
$config['backup']['destination']['filename_prefix'] = 'archive-';
$config['monitor_backups'] = [
    [
        'name' => $config['backup']['name'],
        'disks' => $config['backup']['destination']['disks'],
        'health_checks' => [
            MaximumAgeInDays::class => (int) env('BACKUP_ARCHIVE_MAX_AGE_DAYS', 8),
            MaximumStorageInMegabytes::class => (int) env('BACKUP_ARCHIVE_MAX_STORAGE_MB', 20480),
        ],
    ],
];
$config['cleanup']['default_strategy']['keep_all_backups_for_days'] = (int) env('BACKUP_ARCHIVE_KEEP_ALL_DAYS', 14);
$config['cleanup']['default_strategy']['keep_daily_backups_for_days'] = (int) env('BACKUP_ARCHIVE_KEEP_DAILY_DAYS', 30);
$config['cleanup']['default_strategy']['keep_weekly_backups_for_weeks'] = (int) env('BACKUP_ARCHIVE_KEEP_WEEKLY_WEEKS', 12);
$config['cleanup']['default_strategy']['keep_monthly_backups_for_months'] = (int) env('BACKUP_ARCHIVE_KEEP_MONTHLY_MONTHS', 12);
$config['cleanup']['default_strategy']['keep_yearly_backups_for_years'] = (int) env('BACKUP_ARCHIVE_KEEP_YEARLY_YEARS', 3);
$config['cleanup']['default_strategy']['delete_oldest_backups_when_using_more_megabytes_than'] = (int) env('BACKUP_ARCHIVE_DELETE_OLDEST_WHEN_USING_MORE_MB', 20480);

return $config;

<?php

use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

$config = require __DIR__.'/backup.php';

$config['backup']['name'] = env('BACKUP_DATABASE_NAME', env('APP_NAME', 'gfree-site').'-database');
$config['backup']['source']['files']['include'] = [];
$config['backup']['source']['files']['exclude'] = [];
$config['backup']['destination']['filename_prefix'] = 'db-';
$config['backup']['database_dump_file_timestamp_format'] = 'Y-m-d-H-i-s';
$config['monitor_backups'] = [
    [
        'name' => $config['backup']['name'],
        'disks' => $config['backup']['destination']['disks'],
        'health_checks' => [
            MaximumAgeInDays::class => 1,
            MaximumStorageInMegabytes::class => (int) env('BACKUP_DATABASE_MAX_STORAGE_MB', 2048),
        ],
    ],
];
$config['cleanup']['default_strategy']['keep_all_backups_for_days'] = (int) env('BACKUP_DATABASE_KEEP_ALL_DAYS', 3);
$config['cleanup']['default_strategy']['keep_daily_backups_for_days'] = (int) env('BACKUP_DATABASE_KEEP_DAILY_DAYS', 14);
$config['cleanup']['default_strategy']['keep_weekly_backups_for_weeks'] = (int) env('BACKUP_DATABASE_KEEP_WEEKLY_WEEKS', 4);
$config['cleanup']['default_strategy']['keep_monthly_backups_for_months'] = (int) env('BACKUP_DATABASE_KEEP_MONTHLY_MONTHS', 3);
$config['cleanup']['default_strategy']['keep_yearly_backups_for_years'] = (int) env('BACKUP_DATABASE_KEEP_YEARLY_YEARS', 1);
$config['cleanup']['default_strategy']['delete_oldest_backups_when_using_more_megabytes_than'] = (int) env('BACKUP_DATABASE_DELETE_OLDEST_WHEN_USING_MORE_MB', 2048);

return $config;

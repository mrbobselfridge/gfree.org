<?php

use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

$config = require __DIR__.'/backup.php';

$config['backup']['name'] = env('BACKUP_FULL_NAME', env('APP_NAME', 'gfree-site').'-full');
$config['backup']['destination']['filename_prefix'] = 'full-';
$config['monitor_backups'] = [
    [
        'name' => $config['backup']['name'],
        'disks' => $config['backup']['destination']['disks'],
        'health_checks' => [
            MaximumAgeInDays::class => (int) env('BACKUP_FULL_MAX_AGE_DAYS', 2),
            MaximumStorageInMegabytes::class => (int) env('BACKUP_FULL_MAX_STORAGE_MB', 10240),
        ],
    ],
];

return $config;

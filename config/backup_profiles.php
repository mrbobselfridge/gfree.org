<?php

return [
    'profiles' => [
        'database' => [
            'label' => 'Database Backup',
            'description' => 'Fast backup of CMS content, users, settings, analytics, and file records.',
            'type' => 'Database only',
            'config' => 'backup_database',
            'only' => 'db',
            'enabled' => (bool) env('BACKUP_DATABASE_ENABLED', true),
            'frequency' => env('BACKUP_DATABASE_FREQUENCY', 'every_four_hours'),
            'time' => env('BACKUP_DATABASE_TIME', '00:15'),
            'day' => env('BACKUP_DATABASE_DAY', 'sunday'),
            'interval_days' => (int) env('BACKUP_DATABASE_INTERVAL_DAYS', 1),
            'sort' => 10,
            'included' => [
                'Database content',
                'Users and admin permissions',
                'File Library metadata',
            ],
        ],

        'full' => [
            'label' => 'Full Site Backup',
            'description' => 'Complete restore bundle for the database, public media, and private File Library documents.',
            'type' => 'Database and files',
            'config' => 'backup_full',
            'only' => null,
            'enabled' => (bool) env('BACKUP_FULL_ENABLED', true),
            'frequency' => env('BACKUP_FULL_FREQUENCY', 'daily'),
            'time' => env('BACKUP_FULL_TIME', '01:00'),
            'day' => env('BACKUP_FULL_DAY', 'sunday'),
            'interval_days' => (int) env('BACKUP_FULL_INTERVAL_DAYS', 1),
            'sort' => 20,
            'included' => [
                'Database content',
                'Media Library images',
                'File Library documents',
            ],
        ],

        'archive' => [
            'label' => 'Archive Backup',
            'description' => 'Longer-retention full backup for weekly or every-X-days recovery points.',
            'type' => 'Database and files',
            'config' => 'backup_archive',
            'only' => null,
            'enabled' => (bool) env('BACKUP_ARCHIVE_ENABLED', true),
            'frequency' => env('BACKUP_ARCHIVE_FREQUENCY', 'weekly'),
            'time' => env('BACKUP_ARCHIVE_TIME', '02:00'),
            'day' => env('BACKUP_ARCHIVE_DAY', 'sunday'),
            'interval_days' => (int) env('BACKUP_ARCHIVE_INTERVAL_DAYS', 7),
            'sort' => 30,
            'included' => [
                'Database content',
                'Media Library images',
                'File Library documents',
            ],
        ],
    ],
];

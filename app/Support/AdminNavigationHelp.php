<?php

namespace App\Support;

class AdminNavigationHelp
{
    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return [
            'Dashboard' => 'Your starting point for admin tools and quick account access.',
            'Homepage Content' => 'Build and arrange the main homepage sections below the banner.',
            'Homepage Banners' => 'Manage the rotating hero banners shown at the top of the homepage.',
            'Media Library' => 'Manage image uploads and downloadable files from one library area.',
            'File Library' => 'Manage public and private PDFs, documents, spreadsheets, forms, posters, and handouts.',
            'Ministries' => 'Manage ministry listing cards and individual ministry detail pages.',
            'Pages' => 'Create and edit website pages, nested page paths, and simple redirect URLs.',
            'Analytics' => 'Review site traffic, top pages, referrers, devices, browsers, and recent page views.',
            'Backups' => 'Run and review database, full-site, and archive backups for recovery planning.',
            'Site Settings' => 'Edit church-wide contact details, social links, AI settings, and page defaults.',
            'Navigation Links' => 'Control the public website header navigation links and dropdowns.',
            'Workflow Notifications' => 'Send automatic or manual email updates when selected content areas are created, changed, or deleted.',
            'Users' => 'Manage admin and editor accounts and their allowed admin areas.',
            'User Manual' => 'Open the printable website and admin manual.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function manualAnchors(): array
    {
        return [
            'Dashboard' => 'dashboard',
            'Homepage Content' => 'homepage',
            'Homepage Banners' => 'banners',
            'Media Library' => 'media-library',
            'File Library' => 'media-library',
            'Ministries' => 'ministries',
            'Pages' => 'pages',
            'Analytics' => 'analytics',
            'Backups' => 'backups',
            'Site Settings' => 'settings',
            'Navigation Links' => 'navigation',
            'Workflow Notifications' => 'workflow-notifications',
            'Users' => 'users',
            'User Manual' => 'top',
        ];
    }
}

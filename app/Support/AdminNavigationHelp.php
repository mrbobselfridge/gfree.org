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
            'Announcements' => 'Create and manage public announcements, events, and church updates.',
            'Bulletins' => 'Upload weekly bulletin PDFs and manage their public bulletin pages.',
            'Media Library' => 'Browse, upload, reuse, replace, or delete images used across the site.',
            'File Library' => 'Manage public and private PDFs, documents, spreadsheets, forms, posters, and handouts.',
            'Ministries' => 'Manage ministry listing cards and individual ministry detail pages.',
            'Pages' => 'Create and edit general website pages such as New Here or About.',
            'Leaders' => 'Manage leadership profiles, photos, and public leader detail pages.',
            'Sermons' => 'Manage sermon landing page text and YouTube feed settings.',
            'Analytics' => 'Review site traffic, top pages, referrers, devices, browsers, and recent page views.',
            'Backups' => 'Run and review database, full-site, and archive backups for recovery planning.',
            'Site Settings' => 'Edit church-wide contact details, social links, AI settings, and page defaults.',
            'Navigation Links' => 'Control the public website header navigation links and dropdowns.',
            'Workflow Notifications' => 'Send automatic or manual email updates when selected content areas are created, changed, or deleted.',
            'Users' => 'Manage admin and editor accounts and their allowed admin areas.',
            'User Manual' => 'Open the printable website and admin manual.',
        ];
    }
}

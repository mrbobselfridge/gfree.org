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
            'Ministries' => 'Manage ministry listing cards and individual ministry detail pages.',
            'Pages' => 'Create and edit general website pages such as New Here or About.',
            'Leaders' => 'Manage leadership profiles, photos, and public leader detail pages.',
            'Sermons' => 'Manage sermon landing page text and YouTube feed settings.',
            'Site Settings' => 'Edit church-wide contact details, social links, AI settings, and page defaults.',
            'Navigation Links' => 'Control the public website header navigation links and dropdowns.',
            'Users' => 'Manage admin and editor accounts and their allowed admin areas.',
        ];
    }
}

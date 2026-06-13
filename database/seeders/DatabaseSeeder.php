<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\FileCategory;
use App\Models\HomepageBanner;
use App\Models\Ministry;
use App\Models\NavigationLink;
use App\Models\SiteSetting;
use App\Support\AiBulletinExtractionPrompt;
use App\Support\FileCategoryExtractionInstructions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        SiteSetting::updateOrCreate([
            'church_name' => 'TwyxtCo Church',
        ], [
            'tagline' => 'Somewhere you can belong',
            'sunday_service_times' => '9:15 & 11 AM',
            'address' => '305 Keystone Hill Road',
            'email' => 'office@twyxtco.org',
            'livestream_url' => '/live',
            'giving_url' => '/give',
            'one_church_url' => 'https://twyxtco.onechurchsoftware.com',
            'ai_bulletin_extraction_prompt' => AiBulletinExtractionPrompt::DEFAULT,
        ]);

        collect([
            'Bulletin',
            'Newsletter',
            'Consent Form',
            'Marketing Form',
            'Form',
            'Poster',
            'Policy',
            'Ministry Resource',
            'Event Handout',
            'Spreadsheet',
            FileCategory::DEFAULT_NAME,
        ])->each(fn (string $name, int $index) => FileCategory::updateOrCreate([
            'name' => $name,
        ], [
            'sort_order' => ($index + 1) * 10,
            'extraction_instructions' => FileCategoryExtractionInstructions::forCategory($name),
        ]));

        HomepageBanner::updateOrCreate([
            'title' => 'Grace for real life.',
        ], [
            'eyebrow' => 'Welcome home',
            'subtitle' => 'A church family in central Pennsylvania learning to follow Jesus together with clarity, care, and room for real questions.',
            'button_label' => 'Plan a Visit',
            'button_url' => '/new-here',
            'secondary_button_label' => 'Watch Live',
            'secondary_button_url' => '/live',
            'is_published' => true,
        ]);

        collect([
            ['label' => 'New Here', 'url' => '/new-here', 'sort_order' => 1],
            ['label' => 'Sundays', 'url' => '/sundays', 'sort_order' => 2],
            ['label' => 'Ministries', 'url' => '/ministry', 'sort_order' => 3],
            ['label' => 'Messages', 'url' => '/messages', 'sort_order' => 4],
        ])->each(fn (array $link) => NavigationLink::updateOrCreate([
            'label' => $link['label'],
            'location' => 'header',
        ], [
            'url' => $link['url'],
            'sort_order' => $link['sort_order'],
            'is_published' => true,
        ]));

        collect([
            [
                'name' => 'Visit Sunday',
                'slug' => 'visit-sunday',
                'short_summary' => 'Service times, what to expect, kids check-in, and where to go.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Find Community',
                'slug' => 'find-community',
                'short_summary' => 'Groups, kids, students, and ways to belong beyond Sunday.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Start Serving',
                'slug' => 'start-serving',
                'short_summary' => 'A direct path into teams, prayer, events, giving, and One Church.',
                'sort_order' => 3,
            ],
        ])->each(fn (array $ministry) => Ministry::updateOrCreate([
            'slug' => $ministry['slug'],
        ], [
            'name' => $ministry['name'],
            'short_summary' => $ministry['short_summary'],
            'sort_order' => $ministry['sort_order'],
            'is_published' => true,
        ]));

        collect([
            [
                'title' => 'Family ministry night',
                'slug' => 'family-ministry-night',
                'summary' => 'Registration open now',
                'is_featured' => true,
            ],
            [
                'title' => 'Grace for the week ahead',
                'slug' => 'grace-for-the-week-ahead',
                'summary' => 'Watch the latest sermon',
                'is_featured' => false,
            ],
            [
                'title' => 'Students summer schedule',
                'slug' => 'students-summer-schedule',
                'summary' => 'See upcoming gatherings',
                'is_featured' => false,
            ],
        ])->each(fn (array $announcement) => Announcement::updateOrCreate([
            'slug' => $announcement['slug'],
        ], [
            'title' => $announcement['title'],
            'summary' => $announcement['summary'],
            'publish_at' => now(),
            'is_featured' => $announcement['is_featured'],
            'is_published' => true,
        ]));
    }
}

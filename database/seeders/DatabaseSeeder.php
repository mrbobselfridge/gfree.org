<?php

namespace Database\Seeders;

use App\Models\FileCategory;
use App\Models\HomepageBanner;
use App\Models\NavigationLink;
use App\Models\SiteSetting;
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
            ['label' => 'Messages', 'url' => '/messages', 'sort_order' => 3],
        ])->each(fn (array $link) => NavigationLink::updateOrCreate([
            'label' => $link['label'],
            'location' => 'header',
        ], [
            'url' => $link['url'],
            'sort_order' => $link['sort_order'],
            'is_published' => true,
        ]));

    }
}

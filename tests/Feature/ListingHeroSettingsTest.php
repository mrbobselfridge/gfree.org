<?php

namespace Tests\Feature;

use App\Models\Ministry;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingHeroSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_announcements_listing_uses_configured_hero_settings(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'announcements_small_label' => 'Latest',
            'announcements_title' => 'Church updates',
            'announcements_subtitle' => 'Important things to know this week.',
            'announcements_image_path' => 'site-settings/announcements/updates.jpg',
        ]);

        $this->get('/announcements')
            ->assertOk()
            ->assertSee('Latest')
            ->assertSee('Church updates')
            ->assertSee('Important things to know this week.')
            ->assertSee('/storage/site-settings/announcements/updates.jpg')
            ->assertSee('page-hero--image');
    }

    public function test_leadership_listing_uses_configured_hero_settings(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'leadership_small_label' => 'Our team',
            'leadership_title' => 'Leaders who serve',
            'leadership_subtitle' => 'Meet the people helping gFree follow Jesus.',
            'leadership_image_path' => 'site-settings/leadership/team.jpg',
        ]);

        $this->get('/leadership')
            ->assertOk()
            ->assertSee('Our team')
            ->assertSee('Leaders who serve')
            ->assertSee('Meet the people helping gFree follow Jesus.')
            ->assertSee('/storage/site-settings/leadership/team.jpg')
            ->assertSee('page-hero--image');
    }

    public function test_ministry_listing_uses_configured_hero_settings_and_lists_ministries(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'ministry_small_label' => 'Ministries',
            'ministry_title' => 'Find a place to connect',
            'ministry_subtitle' => 'Groups and teams for every season.',
            'ministry_image_path' => 'site-settings/ministry/ministries.jpg',
        ]);

        Ministry::query()->create([
            'name' => 'Kids Ministry',
            'slug' => 'kids-ministry',
            'short_summary' => 'Helping kids know Jesus.',
            'card_image_path' => 'ministries/card-images/kids.jpg',
            'category' => 'Families',
            'one_church_url' => 'https://example.com/kids',
            'is_published' => true,
        ]);

        $this->get('/ministries')
            ->assertOk()
            ->assertSee('Ministries')
            ->assertSee('Find a place to connect')
            ->assertSee('Groups and teams for every season.')
            ->assertSee('/storage/site-settings/ministry/ministries.jpg')
            ->assertSee('Kids Ministry')
            ->assertSee('Helping kids know Jesus.')
            ->assertSee('/storage/ministries/card-images/kids.jpg')
            ->assertSee('https://example.com/kids');
    }
}

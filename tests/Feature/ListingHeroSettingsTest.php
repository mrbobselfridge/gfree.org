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
            'announcements_subtitle' => '<p>Important things to <strong>know</strong> this week.</p>',
            'announcements_image_path' => 'site-settings/announcements/updates.jpg',
        ]);

        $this->get('/announcements')
            ->assertOk()
            ->assertSee('Latest')
            ->assertSee('Church updates')
            ->assertSee('<strong>know</strong>', false)
            ->assertDontSee('&lt;strong&gt;know&lt;/strong&gt;', false)
            ->assertSee('/storage/site-settings/announcements/updates.jpg')
            ->assertSee('page-hero--image');
    }

    public function test_leadership_listing_uses_configured_hero_settings(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'leadership_small_label' => 'Our team',
            'leadership_title' => 'Leaders who serve',
            'leadership_subtitle' => '<p>Meet the people helping <strong>gFree</strong> follow Jesus.</p>',
            'leadership_image_path' => 'site-settings/leadership/team.jpg',
        ]);

        $this->get('/leadership')
            ->assertOk()
            ->assertSee('Our team')
            ->assertSee('Leaders who serve')
            ->assertSee('<strong>gFree</strong>', false)
            ->assertDontSee('&lt;strong&gt;gFree&lt;/strong&gt;', false)
            ->assertSee('/storage/site-settings/leadership/team.jpg')
            ->assertSee('page-hero--image');
    }

    public function test_ministry_listing_uses_configured_hero_settings_and_lists_ministries(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'ministry_small_label' => 'Ministries',
            'ministry_title' => 'Find a place to connect',
            'ministry_subtitle' => '<p>Groups and <strong>teams</strong> for every season.</p>',
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

        $this->get('/ministry')
            ->assertOk()
            ->assertSee('Ministries')
            ->assertSee('Find a place to connect')
            ->assertSee('<strong>teams</strong>', false)
            ->assertDontSee('&lt;strong&gt;teams&lt;/strong&gt;', false)
            ->assertSee('/storage/site-settings/ministry/ministries.jpg')
            ->assertSee('Kids Ministry')
            ->assertSee('Helping kids know Jesus.')
            ->assertSee('/storage/ministries/card-images/kids.jpg')
            ->assertSee('/ministry/kids-ministry');
    }

    public function test_ministry_detail_shows_ministry_content_and_actions(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
        ]);

        Ministry::query()->create([
            'name' => 'Kids Ministry',
            'slug' => 'kids-ministry',
            'short_summary' => 'Helping kids know Jesus.',
            'description' => '<p>Kids gather during Sunday services.</p>',
            'hero_image_path' => 'ministries/hero-images/kids.jpg',
            'category' => 'Families',
            'meeting_time' => 'Sundays at 10am',
            'location' => 'Kids Wing',
            'leader_name' => 'Jane Doe',
            'leader_email' => 'jane@example.com',
            'one_church_url' => 'https://example.com/kids',
            'is_published' => true,
        ]);

        $this->get('/ministry/kids-ministry')
            ->assertOk()
            ->assertSee('Families')
            ->assertSee('Kids Ministry')
            ->assertSee('Helping kids know Jesus.')
            ->assertSee('Kids gather during Sunday services.', false)
            ->assertSee('/storage/ministries/hero-images/kids.jpg')
            ->assertSee('Sundays at 10am')
            ->assertSee('Kids Wing')
            ->assertSee('Jane Doe')
            ->assertSee('mailto:jane@example.com')
            ->assertSee('https://example.com/kids');
    }
}

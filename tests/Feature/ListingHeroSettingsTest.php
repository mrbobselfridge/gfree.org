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
            'church_name' => 'TwyxtCo Church',
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
            'church_name' => 'TwyxtCo Church',
            'leadership_small_label' => 'Our team',
            'leadership_title' => 'Leaders who serve',
            'leadership_subtitle' => '<p>Meet the people helping <strong>TwyxtCo</strong> follow Jesus.</p>',
            'leadership_image_path' => 'site-settings/leadership/team.jpg',
        ]);

        $this->get('/leadership')
            ->assertOk()
            ->assertSee('Our team')
            ->assertSee('Leaders who serve')
            ->assertSee('<strong>TwyxtCo</strong>', false)
            ->assertDontSee('&lt;strong&gt;TwyxtCo&lt;/strong&gt;', false)
            ->assertSee('/storage/site-settings/leadership/team.jpg')
            ->assertSee('page-hero--image');
    }

    public function test_ministry_listing_uses_configured_hero_settings_and_lists_ministries(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
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
            ->assertSee('listing-card__link', false)
            ->assertSee('listing-card__button', false)
            ->assertSee('/ministry/kids-ministry');
    }

    public function test_ministry_detail_shows_ministry_actions_without_legacy_description(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
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
            'leader_phone' => '814-555-1212',
            'one_church_url' => 'https://example.com/kids',
            'embed_code' => '<iframe src="https://example.com/embed"></iframe>',
            'is_published' => true,
        ]);

        $this->get('/ministry/kids-ministry')
            ->assertOk()
            ->assertSee('Families')
            ->assertSee('Kids Ministry')
            ->assertSee('Helping kids know Jesus.')
            ->assertDontSee('Kids gather during Sunday services.', false)
            ->assertSee('/storage/ministries/hero-images/kids.jpg')
            ->assertSee('ministry-hero-contact', false)
            ->assertDontSee('ministry-detail__sidebar', false)
            ->assertSee('Ministry Leader')
            ->assertSeeInOrder(['Ministry Leader', 'Jane Doe', 'When', 'Sundays at 10am', 'Where', 'Kids Wing', 'Open in One Church'])
            ->assertSee('Sundays at 10am')
            ->assertSee('Kids Wing')
            ->assertSee('Jane Doe')
            ->assertSee('814-555-1212')
            ->assertSee('mailto:jane@example.com')
            ->assertSee('tel:8145551212')
            ->assertDontSee('<iframe src="https://example.com/embed"></iframe>', false)
            ->assertSee('https://example.com/kids');
    }

    public function test_ministries_listing_can_be_searched(): void
    {
        Ministry::query()->create([
            'name' => 'Kids Ministry',
            'slug' => 'kids-ministry',
            'short_summary' => 'Helping kids know Jesus.',
            'is_published' => true,
        ]);

        Ministry::query()->create([
            'name' => 'Care Team',
            'slug' => 'care-team',
            'short_summary' => 'Prayer and care.',
            'is_published' => true,
        ]);

        $this->get('/ministry?search=kids')
            ->assertOk()
            ->assertSee('Search ministries')
            ->assertSee('Kids Ministry')
            ->assertDontSee('Care Team');
    }

    public function test_ministry_listing_groups_by_sort_order_before_randomizing_ties(): void
    {
        Ministry::query()->create([
            'name' => 'Second Sort Ministry',
            'slug' => 'second-sort-ministry',
            'short_summary' => 'Appears after the first sort group.',
            'sort_order' => 20,
            'is_published' => true,
        ]);

        Ministry::query()->create([
            'name' => 'First Sort Ministry',
            'slug' => 'first-sort-ministry',
            'short_summary' => 'Appears before the second sort group.',
            'sort_order' => 10,
            'is_published' => true,
        ]);

        $this->get('/ministry')
            ->assertOk()
            ->assertSeeInOrder([
                'First Sort Ministry',
                'Second Sort Ministry',
            ]);
    }

    public function test_ministry_detail_renders_content_blocks_before_legacy_description(): void
    {
        Ministry::query()->create([
            'name' => 'Care Ministry',
            'slug' => 'care-ministry',
            'short_summary' => 'Care for one another.',
            'description' => '<p>Legacy ministry details.</p>',
            'content_blocks' => [
                [
                    'type' => 'text',
                    'data' => [
                        'eyebrow' => 'Pastoral Care',
                        'heading' => 'Walk with someone this week.',
                        'body' => '<p>Request prayer or join a care team.</p>',
                        'background' => 'teal',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/ministry/care-ministry')
            ->assertOk()
            ->assertSee('Pastoral Care')
            ->assertSee('Walk with someone this week.')
            ->assertSee('Request prayer or join a care team.')
            ->assertSee('page-block--bg-teal', false)
            ->assertDontSee('Legacy ministry details.');
    }

    public function test_ministry_detail_uses_landing_image_when_hero_image_is_missing(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'ministry_image_path' => 'site-settings/ministry/default.jpg',
        ]);

        Ministry::query()->create([
            'name' => 'Students Ministry',
            'slug' => 'students-ministry',
            'short_summary' => 'Students following Jesus.',
            'card_image_path' => 'ministries/card-images/students.jpg',
            'is_published' => true,
        ]);

        $this->get('/ministry/students-ministry')
            ->assertOk()
            ->assertSee('/storage/site-settings/ministry/default.jpg')
            ->assertDontSee('/storage/ministries/card-images/students.jpg')
            ->assertSee('page-hero--image');
    }
}

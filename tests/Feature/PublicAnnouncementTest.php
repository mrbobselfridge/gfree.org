<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_published_announcements_show_on_index(): void
    {
        Announcement::query()->create([
            'title' => 'Church Picnic',
            'slug' => 'church-picnic',
            'summary' => 'Join us after service.',
            'image_path' => 'announcements/picnic.jpg',
            'is_published' => true,
        ]);

        Announcement::query()->create([
            'title' => 'Draft Announcement',
            'slug' => 'draft-announcement',
            'is_published' => false,
        ]);

        $this->get('/announcements')
            ->assertOk()
            ->assertSee('Church Picnic')
            ->assertSee('Join us after service.')
            ->assertSee('/storage/announcements/picnic.jpg')
            ->assertSee('listing-card__link', false)
            ->assertSee('listing-card__button', false)
            ->assertDontSee('Draft Announcement');
    }

    public function test_announcements_listing_can_be_searched(): void
    {
        Announcement::query()->create([
            'title' => 'Youth Retreat',
            'slug' => 'youth-retreat',
            'summary' => 'Student weekend away.',
            'is_published' => true,
        ]);

        Announcement::query()->create([
            'title' => 'Church Picnic',
            'slug' => 'church-picnic',
            'summary' => 'Lunch after service.',
            'is_published' => true,
        ]);

        $this->get('/announcements?search=retreat')
            ->assertOk()
            ->assertSee('Search announcements')
            ->assertSee('Youth Retreat')
            ->assertDontSee('Church Picnic');
    }

    public function test_announcement_detail_requires_current_published_record(): void
    {
        Announcement::query()->create([
            'title' => 'Expired Announcement',
            'slug' => 'expired-announcement',
            'expires_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $this->get('/announcements/expired-announcement')->assertNotFound();
    }

    public function test_announcement_detail_uses_publish_window_not_feature_window(): void
    {
        Announcement::query()->create([
            'title' => 'Published But No Longer Featured',
            'slug' => 'published-but-no-longer-featured',
            'publish_at' => now()->subDays(2),
            'expires_at' => now()->addDay(),
            'featured_at' => now()->subDays(2),
            'feature_expires_at' => now()->subDay(),
            'is_featured' => true,
            'is_published' => true,
        ]);

        $this->get('/announcements/published-but-no-longer-featured')
            ->assertOk()
            ->assertSee('Published But No Longer Featured');
    }

    public function test_announcement_detail_renders_image_body_and_cta(): void
    {
        Announcement::query()->create([
            'title' => 'Baptism Sunday',
            'slug' => 'baptism-sunday',
            'summary' => 'Celebrate new life together.',
            'body' => '<h2>Bring your family.</h2><p>Stay for lunch.</p>',
            'image_path' => 'announcements/baptism.jpg',
            'background' => 'forest',
            'cta_label' => 'Register',
            'cta_url' => 'https://example.com/register',
            'is_featured' => true,
            'is_published' => true,
        ]);

        $this->get('/announcements/baptism-sunday')
            ->assertOk()
            ->assertSee('Featured')
            ->assertSee('Baptism Sunday')
            ->assertSee('Celebrate new life together.')
            ->assertSee('/storage/announcements/baptism.jpg')
            ->assertSee('page-block--bg-forest')
            ->assertSee('Bring your family.')
            ->assertSee('<h2>Bring your family.</h2>', false)
            ->assertSee('Stay for lunch.')
            ->assertSee('Register')
            ->assertSee('https://example.com/register');
    }

    public function test_announcement_detail_renders_content_blocks_before_legacy_body(): void
    {
        Announcement::query()->create([
            'title' => 'Serve Weekend',
            'slug' => 'serve-weekend',
            'summary' => 'Teams serving the city.',
            'body' => '<p>Legacy announcement body.</p>',
            'content_blocks' => [
                [
                    'type' => 'cta',
                    'data' => [
                        'eyebrow' => 'Serve',
                        'heading' => 'Pick a project.',
                        'body' => '<p>Choose a team and invite a friend.</p>',
                        'button_label' => 'Sign up',
                        'button_url' => '/serve',
                        'background' => 'gold',
                        'layout' => 'content_left',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/announcements/serve-weekend')
            ->assertOk()
            ->assertSee('Pick a project.')
            ->assertSee('Choose a team and invite a friend.')
            ->assertSee('Sign up')
            ->assertSee('page-block--bg-gold', false)
            ->assertDontSee('Legacy announcement body.');
    }

    public function test_external_announcement_cta_opens_in_new_tab(): void
    {
        Announcement::query()->create([
            'title' => 'External Signup',
            'slug' => 'external-signup',
            'cta_label' => 'Sign up',
            'cta_url' => 'https://events.example.com/signup',
            'is_published' => true,
        ]);

        $this->get('/announcements/external-signup')
            ->assertOk()
            ->assertSee('<a class="page-block__button" href="https://events.example.com/signup" target="_blank" rel="noopener noreferrer">Sign up</a>', false);
    }

    public function test_announcement_detail_uses_landing_image_when_record_image_is_missing(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'announcements_image_path' => 'site-settings/announcements/default.jpg',
        ]);

        Announcement::query()->create([
            'title' => 'No Image Announcement',
            'slug' => 'no-image-announcement',
            'is_published' => true,
        ]);

        $this->get('/announcements/no-image-announcement')
            ->assertOk()
            ->assertSee('/storage/site-settings/announcements/default.jpg')
            ->assertSee('page-hero--image');
    }
}

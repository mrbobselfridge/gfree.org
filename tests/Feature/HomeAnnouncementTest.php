<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\HomepageContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_announcements_include_images_and_link_to_details(): void
    {
        Announcement::query()->create([
            'title' => 'Church Picnic',
            'slug' => 'church-picnic',
            'summary' => 'Join us after service.',
            'image_path' => 'announcements/picnic.jpg',
            'cta_label' => 'External Signup',
            'cta_url' => 'https://example.com/signup',
            'is_featured' => true,
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('concept-updates--bar', false)
            ->assertSee('Church Picnic')
            ->assertSee('/storage/announcements/picnic.jpg')
            ->assertSee('/announcements/church-picnic')
            ->assertDontSee('https://example.com/signup');
    }

    public function test_homepage_announcements_bar_shows_up_to_ten_featured_announcements(): void
    {
        foreach (range(1, 11) as $index) {
            Announcement::query()->create([
                'title' => "Featured Update {$index}",
                'slug' => "featured-update-{$index}",
                'summary' => "Homepage update {$index}.",
                'featured_at' => now()->subMinutes($index),
                'is_featured' => true,
                'is_published' => true,
            ]);
        }

        $this->get('/')
            ->assertOk()
            ->assertSee('Featured Update 1')
            ->assertSee('Featured Update 10')
            ->assertDontSee('Featured Update 11')
            ->assertSee('Homepage update 10.')
            ->assertDontSee('Homepage update 11.');
    }

    public function test_homepage_only_shows_announcements_inside_feature_window(): void
    {
        Announcement::query()->create([
            'title' => 'Featured Now',
            'slug' => 'featured-now',
            'featured_at' => now()->subHour(),
            'feature_expires_at' => now()->addHour(),
            'is_featured' => true,
            'is_published' => true,
        ]);

        Announcement::query()->create([
            'title' => 'Featured Later',
            'slug' => 'featured-later',
            'featured_at' => now()->addDay(),
            'is_featured' => true,
            'is_published' => true,
        ]);

        Announcement::query()->create([
            'title' => 'Featured Before',
            'slug' => 'featured-before',
            'feature_expires_at' => now()->subDay(),
            'is_featured' => true,
            'is_published' => true,
        ]);

        Announcement::query()->create([
            'title' => 'Not Featured',
            'slug' => 'not-featured',
            'is_featured' => false,
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Featured Now')
            ->assertDontSee('Featured Later')
            ->assertDontSee('Featured Before')
            ->assertDontSee('Not Featured');
    }

    public function test_homepage_feature_window_falls_back_to_publish_window(): void
    {
        Announcement::query()->create([
            'title' => 'Published Featured Announcement',
            'slug' => 'published-featured-announcement',
            'publish_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
            'is_featured' => true,
            'is_published' => true,
        ]);

        Announcement::query()->create([
            'title' => 'Expired Published Announcement',
            'slug' => 'expired-published-announcement',
            'publish_at' => now()->subDays(2),
            'expires_at' => now()->subDay(),
            'is_featured' => true,
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Published Featured Announcement')
            ->assertDontSee('Expired Published Announcement');
    }

    public function test_homepage_announcements_can_be_hidden_from_content_blocks(): void
    {
        Announcement::query()->create([
            'title' => 'Hidden Announcement',
            'slug' => 'hidden-announcement',
            'summary' => 'Do not show on homepage.',
            'is_featured' => true,
            'is_published' => true,
        ]);

        HomepageContent::query()->create([
            'content_blocks' => [
                [
                    'type' => 'announcements_bar',
                    'data' => [
                        'is_visible' => false,
                        'background' => 'black',
                    ],
                ],
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('concept-updates--bar', false)
            ->assertDontSee('Hidden Announcement');
    }

    public function test_homepage_announcements_bar_can_be_moved_and_change_background(): void
    {
        Announcement::query()->create([
            'title' => 'Movable Announcement',
            'slug' => 'movable-announcement',
            'summary' => 'This section can move.',
            'is_featured' => true,
            'is_published' => true,
        ]);

        HomepageContent::query()->create([
            'content_blocks' => [
                [
                    'type' => 'announcements_bar',
                    'data' => [
                        'is_visible' => true,
                        'heading' => 'Current News',
                        'link_label' => 'All news',
                        'link_url' => '/announcements',
                        'background' => 'forest',
                    ],
                ],
                [
                    'type' => 'text',
                    'data' => [
                        'heading' => 'After the bar',
                        'body' => '<p>This content follows announcements.</p>',
                        'background' => 'white',
                    ],
                ],
            ],
        ]);

        $response = $this->get('/')
            ->assertOk()
            ->assertSee('concept-updates--bg-forest', false)
            ->assertSee('Current News')
            ->assertSee('All news')
            ->assertSee('Movable Announcement')
            ->assertSee('After the bar');

        $this->assertLessThan(
            strpos($response->getContent(), 'After the bar'),
            strpos($response->getContent(), 'Movable Announcement'),
        );
    }

    public function test_homepage_only_renders_one_announcements_bar(): void
    {
        Announcement::query()->create([
            'title' => 'Single Bar Announcement',
            'slug' => 'single-bar-announcement',
            'summary' => 'Only one section should render.',
            'is_featured' => true,
            'is_published' => true,
        ]);

        HomepageContent::query()->create([
            'content_blocks' => [
                [
                    'type' => 'announcements_bar',
                    'data' => ['is_visible' => true, 'heading' => 'First', 'background' => 'white'],
                ],
                [
                    'type' => 'announcements_bar',
                    'data' => ['is_visible' => true, 'heading' => 'Second', 'background' => 'black'],
                ],
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('First')
            ->assertDontSee('Second');
    }
}

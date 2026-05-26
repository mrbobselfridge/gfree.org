<?php

namespace Tests\Feature;

use App\Models\Announcement;
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
            ->assertSee('Church Picnic')
            ->assertSee('/storage/announcements/picnic.jpg')
            ->assertSee('/announcements/church-picnic')
            ->assertDontSee('https://example.com/signup');
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
}

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
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Church Picnic')
            ->assertSee('/storage/announcements/picnic.jpg')
            ->assertSee('/announcements/church-picnic')
            ->assertDontSee('https://example.com/signup');
    }
}

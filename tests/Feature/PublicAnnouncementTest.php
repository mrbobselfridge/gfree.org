<?php

namespace Tests\Feature;

use App\Models\Announcement;
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
            ->assertDontSee('Draft Announcement');
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
}

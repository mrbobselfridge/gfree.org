<?php

namespace Tests\Feature;

use App\Models\StaffMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_leaders_show_on_leadership_index(): void
    {
        StaffMember::query()->create([
            'name' => 'Jane Leader',
            'slug' => 'jane-leader',
            'role' => 'Pastor',
            'photo_path' => 'leadership/jane.jpg',
            'is_published' => true,
        ]);

        StaffMember::query()->create([
            'name' => 'Draft Leader',
            'slug' => 'draft-leader',
            'is_published' => false,
        ]);

        $this->get('/leadership')
            ->assertOk()
            ->assertSee('Jane Leader')
            ->assertSee('Pastor')
            ->assertSee('/storage/leadership/jane.jpg')
            ->assertSee('/leadership/jane-leader')
            ->assertDontSee('Draft Leader');
    }

    public function test_leader_profile_renders_photo_rich_bio_and_email(): void
    {
        StaffMember::query()->create([
            'name' => 'John Shepherd',
            'slug' => 'john-shepherd',
            'role' => 'Care Director',
            'bio' => '<p>John helps people find care.</p><ul><li>Prayer</li></ul>',
            'photo_path' => 'leadership/john.jpg',
            'email' => 'john@example.com',
            'is_published' => true,
        ]);

        $this->get('/leadership/john-shepherd')
            ->assertOk()
            ->assertSee('Leadership')
            ->assertSee('John Shepherd')
            ->assertSee('Care Director')
            ->assertSee('/storage/leadership/john.jpg')
            ->assertSee('<p>John helps people find care.</p>', false)
            ->assertSee('<li>Prayer</li>', false)
            ->assertSee('mailto:john@example.com')
            ->assertSee('Email John Shepherd');
    }

    public function test_unpublished_leader_profile_is_not_public(): void
    {
        StaffMember::query()->create([
            'name' => 'Hidden Leader',
            'slug' => 'hidden-leader',
            'is_published' => false,
        ]);

        $this->get('/leadership/hidden-leader')->assertNotFound();
    }
}

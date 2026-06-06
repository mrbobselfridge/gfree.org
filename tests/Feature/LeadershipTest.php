<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
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
            ->assertSee('listing-card__link', false)
            ->assertSee('listing-card__button', false)
            ->assertDontSee('Draft Leader');
    }

    public function test_leadership_listing_can_be_searched(): void
    {
        StaffMember::query()->create([
            'name' => 'Jane Leader',
            'slug' => 'jane-leader',
            'role' => 'Care Pastor',
            'is_published' => true,
        ]);

        StaffMember::query()->create([
            'name' => 'Sam Teacher',
            'slug' => 'sam-teacher',
            'role' => 'Teaching Pastor',
            'is_published' => true,
        ]);

        $this->get('/leadership?search=care')
            ->assertOk()
            ->assertSee('Search leaders')
            ->assertSee('Jane Leader')
            ->assertDontSee('Sam Teacher');
    }

    public function test_leadership_listing_groups_by_sort_order_before_randomizing_ties(): void
    {
        StaffMember::query()->create([
            'name' => 'Second Sort Leader',
            'slug' => 'second-sort-leader',
            'sort_order' => 20,
            'is_published' => true,
        ]);

        StaffMember::query()->create([
            'name' => 'First Sort Leader',
            'slug' => 'first-sort-leader',
            'sort_order' => 10,
            'is_published' => true,
        ]);

        $this->get('/leadership')
            ->assertOk()
            ->assertSeeInOrder([
                'First Sort Leader',
                'Second Sort Leader',
            ]);
    }

    public function test_leader_profile_renders_photo_and_email_without_legacy_bio(): void
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
            ->assertDontSee('<p>John helps people find care.</p>', false)
            ->assertDontSee('<li>Prayer</li>', false)
            ->assertSee('mailto:john@example.com')
            ->assertSee('Email John Shepherd');
    }

    public function test_leader_profile_renders_content_blocks_before_legacy_bio(): void
    {
        StaffMember::query()->create([
            'name' => 'Jamie Leader',
            'slug' => 'jamie-leader',
            'role' => 'Students Director',
            'bio' => '<p>Legacy leader bio.</p>',
            'content_blocks' => [
                [
                    'type' => 'image_text',
                    'data' => [
                        'image_path' => 'leadership/content-images/students.jpg',
                        'image_alt' => 'Students gathering',
                        'heading' => 'Leading students toward Jesus.',
                        'body' => '<p>Jamie supports leaders and families.</p>',
                        'background' => 'forest',
                        'image_position' => 'right',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/leadership/jamie-leader')
            ->assertOk()
            ->assertSee('Leading students toward Jesus.')
            ->assertSee('Jamie supports leaders and families.')
            ->assertSee('/storage/leadership/content-images/students.jpg')
            ->assertSee('Students gathering')
            ->assertSee('page-block--bg-forest', false)
            ->assertDontSee('Legacy leader bio.');
    }

    public function test_leader_profile_uses_landing_image_when_photo_is_missing(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'leadership_image_path' => 'site-settings/leadership/default.jpg',
        ]);

        StaffMember::query()->create([
            'name' => 'No Photo Leader',
            'slug' => 'no-photo-leader',
            'role' => 'Director',
            'is_published' => true,
        ]);

        $this->get('/leadership/no-photo-leader')
            ->assertOk()
            ->assertSee('/storage/site-settings/leadership/default.jpg')
            ->assertSee('page-hero--image');
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

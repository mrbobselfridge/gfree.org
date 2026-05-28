<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_pages_are_available_by_slug(): void
    {
        Page::query()->create([
            'title' => 'About gFree',
            'slug' => 'about',
            'intro' => 'A short intro for the page.',
            'body' => 'This is the page body.',
            'is_published' => true,
        ]);

        $this->get('/about')
            ->assertOk()
            ->assertSee('About gFree')
            ->assertSee('A short intro for the page.')
            ->assertSee('This is the page body.')
            ->assertDontSee('<p class="concept-eyebrow">gFree Church</p>', false);
    }

    public function test_page_hero_label_is_optional_and_page_specific(): void
    {
        Page::query()->create([
            'title' => 'New Here',
            'slug' => 'new-here',
            'hero_label' => 'Start Here',
            'is_published' => true,
        ]);

        $this->get('/new-here')
            ->assertOk()
            ->assertSee('Start Here')
            ->assertSee('New Here');
    }

    public function test_unpublished_pages_are_not_public(): void
    {
        Page::query()->create([
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'is_published' => false,
        ]);

        $this->get('/draft-page')->assertNotFound();
    }

    public function test_structured_page_blocks_render_before_legacy_body(): void
    {
        Page::query()->create([
            'title' => 'New Here',
            'slug' => 'new-here',
            'body' => 'Legacy fallback text.',
            'content_blocks' => [
                [
                    'type' => 'text',
                    'data' => [
                        'eyebrow' => 'Start Here',
                        'heading' => 'Plan your visit',
                        'body' => '<p>Everything you need for Sunday.</p>',
                        'background' => 'light',
                    ],
                ],
                [
                    'type' => 'cta',
                    'data' => [
                        'heading' => 'Ready to connect?',
                        'body' => '<p>Send us a <strong>note</strong> before you visit.</p>',
                        'button_label' => 'Contact Us',
                        'button_url' => '/contact',
                        'style' => 'dark',
                        'layout' => 'button_bottom',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/new-here')
            ->assertOk()
            ->assertSee('Start Here')
            ->assertSee('Plan your visit')
            ->assertSee('Everything you need for Sunday.')
            ->assertSee('Ready to connect?')
            ->assertSee('page-block--cta-button-bottom', false)
            ->assertSee('<strong>note</strong>', false)
            ->assertSee('Contact Us')
            ->assertDontSee('Legacy fallback text.');
    }

    public function test_structured_blocks_can_render_without_headings(): void
    {
        Page::query()->create([
            'title' => 'Students',
            'slug' => 'students',
            'content_blocks' => [
                [
                    'type' => 'text',
                    'data' => [
                        'admin_label' => 'Admin only name',
                        'body' => '<p>Student ministry details.</p>',
                        'background' => 'white',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/students')
            ->assertOk()
            ->assertSee('Student ministry details.')
            ->assertDontSee('Admin only name');
    }

    public function test_image_blocks_do_not_require_body_content(): void
    {
        Page::query()->create([
            'title' => 'Students',
            'slug' => 'students',
            'content_blocks' => [
                [
                    'type' => 'image_text',
                    'data' => [
                        'image_path' => 'pages/content-images/students.jpg',
                        'image_alt' => 'Students worshiping together',
                        'image_position' => 'screen_width',
                        'background' => 'white',
                        'body' => '<p><br></p>',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/students')
            ->assertOk()
            ->assertSee('Students worshiping together')
            ->assertSee('page-block--image-screenwidth')
            ->assertSee('page-block--image-only')
            ->assertDontSee('page-image-text__content');
    }

    public function test_page_info_strip_can_pull_office_hours_from_site_settings(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'office_hours' => '<p>Monday-Thursday <strong>9 AM-4 PM</strong></p>',
        ]);

        Page::query()->create([
            'title' => 'Visit',
            'slug' => 'visit',
            'content_blocks' => [
                [
                    'type' => 'info_strip',
                    'data' => [
                        'items' => [
                            ['label' => 'Office', 'source' => 'office_hours', 'value' => 'Fallback Hours'],
                        ],
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/visit')
            ->assertOk()
            ->assertSee('concept-service-strip', false)
            ->assertSee('<strong>9 AM-4 PM</strong>', false)
            ->assertDontSee('Fallback Hours');
    }

    public function test_page_announcements_bar_renders_featured_announcements(): void
    {
        Announcement::query()->create([
            'title' => 'Page Announcement',
            'slug' => 'page-announcement',
            'summary' => 'This should appear on a page.',
            'image_path' => 'announcements/page.jpg',
            'is_featured' => true,
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Visit',
            'slug' => 'visit',
            'content_blocks' => [
                [
                    'type' => 'announcements_bar',
                    'data' => [
                        'is_visible' => true,
                        'heading' => 'Page News',
                        'link_label' => 'All announcements',
                        'link_url' => '/announcements',
                        'background' => 'teal',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/visit')
            ->assertOk()
            ->assertSee('concept-updates--bar', false)
            ->assertSee('concept-updates--bg-teal', false)
            ->assertSee('Page News')
            ->assertSee('All announcements')
            ->assertSee('Page Announcement')
            ->assertSee('This should appear on a page.')
            ->assertSee('/storage/announcements/page.jpg')
            ->assertSee('/announcements/page-announcement');
    }

    public function test_process_step_blocks_render_on_public_pages(): void
    {
        Page::query()->create([
            'title' => 'Serve',
            'slug' => 'serve',
            'content_blocks' => [
                [
                    'type' => 'process_steps',
                    'data' => [
                        'eyebrow' => 'Ready to serve?',
                        'heading' => 'Start with three steps.',
                        'background' => 'black',
                        'steps' => [
                            ['title' => 'Fill out the form', 'summary' => 'Tell us where you are interested.'],
                            ['title' => 'Talk with a leader', 'summary' => 'Find a team that fits your gifts.'],
                        ],
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/serve')
            ->assertOk()
            ->assertSee('page-block--process-steps', false)
            ->assertSee('page-block--bg-black', false)
            ->assertSee('Ready to serve?')
            ->assertSee('Start with three steps.')
            ->assertSee('Fill out the form')
            ->assertSee('Find a team that fits your gifts.');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Page;
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
                        'body' => 'Send us a note before you visit.',
                        'button_label' => 'Contact Us',
                        'button_url' => '/contact',
                        'style' => 'dark',
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
                        'image_position' => 'center',
                        'background' => 'white',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/students')
            ->assertOk()
            ->assertSee('Students worshiping together')
            ->assertSee('page-block--image-center');
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

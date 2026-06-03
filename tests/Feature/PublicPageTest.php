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
            ->assertSee('concept-header', false)
            ->assertSee('site-footer', false)
            ->assertDontSee('<p class="concept-eyebrow">gFree Church</p>', false);
    }

    public function test_page_can_hide_navigation_and_footer_for_minimal_landing_pages(): void
    {
        Page::query()->create([
            'title' => 'Mobile Landing',
            'slug' => 'mobile-landing',
            'intro' => 'A focused landing page.',
            'body' => 'Only the page content should show.',
            'show_site_chrome' => false,
            'is_published' => true,
        ]);

        $this->get('/mobile-landing')
            ->assertOk()
            ->assertSee('Mobile Landing')
            ->assertSee('A focused landing page.')
            ->assertSee('Only the page content should show.')
            ->assertDontSee('concept-header', false)
            ->assertDontSee('site-footer', false);
    }

    public function test_public_footer_uses_logo_contact_details_and_social_icons(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'tagline' => 'This tagline should not be in the footer.',
            'address' => '<p>305 Keystone Hill Road</p>',
            'email' => 'hello@example.com',
            'phone' => '(814) 555-1212',
            'facebook_url' => 'https://facebook.example/gfree',
            'instagram_url' => 'https://instagram.example/gfree',
            'youtube_url' => 'https://youtube.example/gfree',
        ]);

        Page::query()->create([
            'title' => 'About',
            'slug' => 'about',
            'intro' => 'About page intro.',
            'is_published' => true,
        ]);

        $this->get('/about')
            ->assertOk()
            ->assertSee('site-footer__brand', false)
            ->assertSee('<a href="'.url('/').'" aria-label="gFree Church home">', false)
            ->assertSee('site-footer__contact', false)
            ->assertSee('site-footer__social', false)
            ->assertSee('Address')
            ->assertSee('Email')
            ->assertSee('Phone')
            ->assertSee('gFree Church')
            ->assertSee('305 Keystone Hill Road')
            ->assertDontSee('https://www.google.com/maps/search/?api=1', false)
            ->assertSee('mailto:hello@example.com', false)
            ->assertSee('tel:8145551212', false)
            ->assertSee('aria-label="Facebook"', false)
            ->assertSee('site-footer__social-link--facebook', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('title="Instagram"', false)
            ->assertSee('site-footer__social-link--youtube', false)
            ->assertSee('site-footer__social-label', false)
            ->assertDontSee('This tagline should not be in the footer.');
    }

    public function test_page_can_hide_site_chrome_and_page_header_for_content_only_pages(): void
    {
        Page::query()->create([
            'title' => 'Content Only Landing',
            'slug' => 'content-only-landing',
            'intro' => 'This intro belongs to the hidden page header.',
            'body' => 'Only this body content should show.',
            'show_site_chrome' => false,
            'show_page_header' => false,
            'is_published' => true,
        ]);

        $this->get('/content-only-landing')
            ->assertOk()
            ->assertSee('Only this body content should show.')
            ->assertDontSee('concept-header', false)
            ->assertDontSee('site-footer', false)
            ->assertDontSee('page-hero', false)
            ->assertDontSee('<h1>Content Only Landing</h1>', false)
            ->assertDontSee('<p>This intro belongs to the hidden page header.</p>', false);
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
                        'content_width' => 'wide',
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
            ->assertSee('page-block__inner--text-wide', false)
            ->assertSee('Everything you need for Sunday.')
            ->assertSee('Ready to connect?')
            ->assertSee('page-block--cta-button-bottom', false)
            ->assertSee('<strong>note</strong>', false)
            ->assertSee('Contact Us')
            ->assertDontSee('Legacy fallback text.');
    }

    public function test_external_cta_buttons_open_in_new_tabs_and_local_ctas_do_not(): void
    {
        Page::query()->create([
            'title' => 'Connect',
            'slug' => 'connect',
            'content_blocks' => [
                [
                    'type' => 'cta',
                    'data' => [
                        'heading' => 'External form',
                        'button_label' => 'Open Form',
                        'button_url' => 'https://forms.example.com/connect',
                        'background' => 'white',
                    ],
                ],
                [
                    'type' => 'image_text',
                    'data' => [
                        'heading' => 'Local page',
                        'button_label' => 'Contact Us',
                        'button_url' => '/contact',
                        'background' => 'white',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $content = $this->get('/connect')
            ->assertOk()
            ->assertSee('<a class="page-block__button" href="https://forms.example.com/connect" target="_blank" rel="noopener noreferrer">Open Form</a>', false)
            ->assertSee('<a class="page-block__button" href="/contact">Contact Us</a>', false)
            ->content();

        $this->assertStringNotContainsString('href="/contact" target="_blank"', $content);
    }

    public function test_short_link_card_rows_are_centered(): void
    {
        Page::query()->create([
            'title' => 'Serve',
            'slug' => 'serve',
            'content_blocks' => [
                [
                    'type' => 'link_cards',
                    'data' => [
                        'heading' => 'Serving teams',
                        'background' => 'white',
                        'cards' => [
                            [
                                'title' => 'Kids',
                                'summary' => 'Help children know Jesus.',
                                'url' => '/kids',
                            ],
                            [
                                'title' => 'Welcome',
                                'summary' => 'Make Sunday clear for guests.',
                                'url' => '/welcome',
                            ],
                        ],
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/serve')
            ->assertOk()
            ->assertSee('Serving teams')
            ->assertSee('page-link-cards--centered', false)
            ->assertSee('page-link-cards--count-2', false)
            ->assertSee('Kids')
            ->assertSee('Welcome');
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
            ->assertSee('page-block__inner--text-medium', false)
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

    public function test_embed_blocks_render_raw_provider_code_on_public_pages(): void
    {
        $embedCode = '<div class="ocs-embed" data-ocs-bg="#ffffff" data-ocs-tenant="gfree" data-ocs-embed="events/calendar" data-ocs-calendars="1,17,16,2,7,10,6,5"></div>'
            .'<script async src="https://cdn.onechurchsoftware.com/embed/v3.1.js"></script>';

        Page::query()->create([
            'title' => 'Calendar',
            'slug' => 'calendar',
            'content_blocks' => [
                [
                    'type' => 'embed',
                    'data' => [
                        'heading' => 'Church Calendar',
                        'embed_code' => $embedCode,
                        'background' => 'white',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/calendar')
            ->assertOk()
            ->assertSee('Church Calendar')
            ->assertSee('<div class="ocs-embed" data-ocs-bg="#ffffff" data-ocs-tenant="gfree" data-ocs-embed="events/calendar" data-ocs-calendars="1,17,16,2,7,10,6,5"></div>', false)
            ->assertSee('<script async src="https://cdn.onechurchsoftware.com/embed/v3.1.js"></script>', false);
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

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
            'title' => 'About TwyxtCo',
            'slug' => 'about',
            'intro' => 'A short intro for the page.',
            'body' => 'This is the page body.',
            'is_published' => true,
        ]);

        $this->get('/about')
            ->assertOk()
            ->assertSee('About TwyxtCo')
            ->assertSee('A short intro for the page.')
            ->assertSee('This is the page body.')
            ->assertSee('concept-header', false)
            ->assertSee('site-footer', false)
            ->assertDontSee('<p class="concept-eyebrow">TwyxtCo Church</p>', false);
    }

    public function test_published_pages_are_available_by_nested_slug_path(): void
    {
        Page::query()->create([
            'title' => 'Baptism Basics',
            'slug' => 'learn/baptism/basics',
            'intro' => 'What baptism means at TwyxtCo.',
            'body' => 'A page with an SEO-friendly path.',
            'is_published' => true,
        ]);

        $this->get('/learn/baptism/basics')
            ->assertOk()
            ->assertSee('Baptism Basics')
            ->assertSee('What baptism means at TwyxtCo.')
            ->assertSee('A page with an SEO-friendly path.');
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
            ->assertSee('public-page--without-site-chrome', false)
            ->assertSee('public-page--with-page-header', false)
            ->assertDontSee('concept-header', false)
            ->assertDontSee('site-footer', false);
    }

    public function test_public_footer_uses_logo_contact_details_and_social_icons(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'site_logo_path' => 'site-settings/logo/custom-logo.png',
            'tagline' => 'This tagline should not be in the footer.',
            'address' => '<p>305 Keystone Hill Road</p>',
            'email' => 'hello@example.com',
            'phone' => '(814) 555-1212',
            'facebook_url' => 'https://facebook.example/twyxtco',
            'instagram_url' => 'https://instagram.example/twyxtco',
            'youtube_url' => 'https://youtube.example/twyxtco',
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
            ->assertSee('<a href="'.url('/').'" aria-label="TwyxtCo Church home">', false)
            ->assertSee('/storage/site-settings/logo/custom-logo.png')
            ->assertSee('site-footer__contact', false)
            ->assertSee('site-footer__social', false)
            ->assertSee('Address')
            ->assertSee('Email')
            ->assertSee('Phone')
            ->assertSee('TwyxtCo Church')
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
            ->assertSee('public-page--without-site-chrome', false)
            ->assertSee('public-page--without-page-header', false)
            ->assertDontSee('concept-header', false)
            ->assertDontSee('site-footer', false)
            ->assertDontSee('page-hero', false)
            ->assertDontSee('<h1>Content Only Landing</h1>', false)
            ->assertDontSee('<p>This intro belongs to the hidden page header.</p>', false);
    }

    public function test_page_can_hide_page_header_while_keeping_site_chrome(): void
    {
        Page::query()->create([
            'title' => 'Embedded Sermon',
            'slug' => 'embedded-sermon',
            'intro' => 'This intro belongs to the hidden page header.',
            'show_page_header' => false,
            'content_blocks' => [
                [
                    'type' => 'embed',
                    'data' => [
                        'background' => 'white',
                        'embed_code' => '<iframe src="https://www.youtube.com/embed/example"></iframe>',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/embedded-sermon')
            ->assertOk()
            ->assertSee('concept-header', false)
            ->assertSee('site-footer', false)
            ->assertSee('public-page--with-site-chrome', false)
            ->assertSee('public-page--without-page-header', false)
            ->assertSee('public-page__main', false)
            ->assertSee('page-block--embed', false)
            ->assertDontSee('page-hero', false)
            ->assertDontSee('<h1>Embedded Sermon</h1>', false)
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

    public function test_pages_respect_publish_and_expiration_dates(): void
    {
        Page::query()->create([
            'title' => 'Always Active Page',
            'slug' => 'always-active-page',
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Active Window Page',
            'slug' => 'active-window-page',
            'publish_at' => now()->subHour(),
            'expires_at' => now()->addHour(),
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Future Page',
            'slug' => 'future-page',
            'publish_at' => now()->addDay(),
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Expired Page',
            'slug' => 'expired-page',
            'expires_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $this->get('/always-active-page')->assertOk()->assertSee('Always Active Page');
        $this->get('/active-window-page')->assertOk()->assertSee('Active Window Page');
        $this->get('/future-page')->assertNotFound();
        $this->get('/expired-page')->assertNotFound();
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
                        'content_width' => 'small',
                    ],
                ],
                [
                    'type' => 'header_message_box',
                    'data' => [
                        'body' => '<p>gFree Church is a relaxed, welcoming place.</p>',
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
            ->assertSee('page-block__inner--text-small', false)
            ->assertSee('<strong>note</strong>', false)
            ->assertSee('Contact Us')
            ->assertSee('page-block--header-message-box', false)
            ->assertSee('gFree Church is a relaxed, welcoming place.')
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

    public function test_link_card_rows_wrap_for_centered_short_rows(): void
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
                                'type' => 'link_same',
                                'url' => '/kids',
                            ],
                            [
                                'title' => 'Download',
                                'summary' => 'Open the PDF.',
                                'type' => 'link_new',
                                'url' => '/storage/media/serve.pdf',
                            ],
                            [
                                'title' => 'Flip',
                                'summary' => 'Click for more.',
                                'key' => 'flip123',
                                'type' => 'flip_html',
                                'html' => '<strong>Back details</strong>',
                            ],
                            [
                                'title' => 'Flip Image',
                                'summary' => 'Click for photo.',
                                'key' => 'flipimage123',
                                'type' => 'flip_image',
                                'image_path' => 'pages/content-images/serve.jpg',
                                'image_alt' => 'Serving team photo',
                                'image_fit' => 'contain',
                                'image_focus' => 'top',
                                'image_zoom' => 125,
                            ],
                            [
                                'title' => 'Widget',
                                'summary' => 'Rendered by JavaScript.',
                                'key' => 'widget123',
                                'type' => 'javascript_widget',
                                'javascript' => "document.getElementById('content-card-widget-widget123').textContent = 'Loaded widget';",
                            ],
                            [
                                'title' => 'Care',
                                'summary' => 'Support people through hard seasons.',
                                'url' => '/care',
                            ],
                            [
                                'title' => 'Unsafe',
                                'summary' => 'Should not become a JavaScript href.',
                                'type' => 'link_same',
                                'url' => 'javascript:void(0)',
                            ],
                            [
                                'title' => 'Production',
                                'summary' => 'Help services run clearly.',
                                'url' => '',
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
            ->assertSee('class="page-link-cards"', false)
            ->assertSee('<a class="page-link-card" href="/kids">', false)
            ->assertSee('<a class="page-link-card" href="/storage/media/serve.pdf" target="_blank" rel="noopener noreferrer">', false)
            ->assertSee('id="content-card-flip-flip123"', false)
            ->assertSee('<strong>Back details</strong>', false)
            ->assertSee('id="content-card-flip-flipimage123"', false)
            ->assertSee('/storage/pages/content-images/serve.jpg')
            ->assertSee('alt="Serving team photo"', false)
            ->assertSee('page-link-card__flip-image--contain', false)
            ->assertSee('object-position: center top; transform: scale(1.25); transform-origin: center top;', false)
            ->assertSee('id="content-card-widget-widget123"', false)
            ->assertSee("document.getElementById('content-card-widget-widget123').textContent = 'Loaded widget';", false)
            ->assertSee('<div class="page-link-card">', false)
            ->assertDontSee('href="javascript:void(0)"', false)
            ->assertDontSee('href="/production"', false)
            ->assertDontSee('href="#"', false)
            ->assertSee('Kids')
            ->assertSee('Download')
            ->assertSee('Flip')
            ->assertSee('Flip Image')
            ->assertSee('Widget')
            ->assertSee('Care')
            ->assertSee('Unsafe')
            ->assertSee('Production');
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

    public function test_code_blocks_render_raw_code_with_and_without_page_block_wrapper(): void
    {
        Page::query()->create([
            'title' => 'Interactive',
            'slug' => 'interactive',
            'content_blocks' => [
                [
                    'type' => 'code',
                    'data' => [
                        'title' => 'Visible feature',
                        'background' => 'teal',
                        'content_width' => 'full',
                        'code' => '<div id="full-code-feature"><script>window.fullCodeFeature = true;</script></div>',
                    ],
                ],
                [
                    'type' => 'code',
                    'data' => [
                        'title' => 'Script only',
                        'background' => 'gold',
                        'content_width' => 'none',
                        'code' => '<script>window.scriptOnlyCodeBlock = true;</script>',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $content = $this->get('/interactive')
            ->assertOk()
            ->assertSee('page-block--code', false)
            ->assertSee('page-block--bg-teal', false)
            ->assertSee('page-block__inner--full', false)
            ->assertSee('<script>window.fullCodeFeature = true;</script>', false)
            ->assertSee('<script>window.scriptOnlyCodeBlock = true;</script>', false)
            ->assertDontSee('Visible feature')
            ->assertDontSee('Script only')
            ->content();

        $this->assertStringNotContainsString('page-block--bg-gold', $content);
        $this->assertSame(1, substr_count($content, 'page-block--code'));
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
            'church_name' => 'TwyxtCo Church',
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
        foreach (range(1, 11) as $index) {
            Announcement::query()->create([
                'title' => "Page Announcement {$index}",
                'slug' => "page-announcement-{$index}",
                'summary' => "This should appear on a page {$index}.",
                'image_path' => $index === 1 ? 'announcements/page.jpg' : null,
                'featured_at' => now()->subMinutes($index),
                'feature_expires_at' => now()->addDays($index),
                'is_featured' => true,
                'is_published' => true,
            ]);
        }

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
            ->assertSee('Page Announcement 1')
            ->assertSee('Page Announcement 10')
            ->assertDontSee('Page Announcement 11')
            ->assertSee('This should appear on a page 1.')
            ->assertSee('This should appear on a page 10.')
            ->assertDontSee('This should appear on a page 11.')
            ->assertSee('/storage/announcements/page.jpg')
            ->assertSee('/announcements/page-announcement-1')
            ->assertSee('/announcements/page-announcement-10')
            ->assertDontSee('/announcements/page-announcement-11');
    }

    public function test_page_announcements_bar_uses_public_sort_order(): void
    {
        $samePublishAt = now()->subDays(3);

        foreach ([
            [
                'title' => 'Content Feature Expires Soon',
                'slug' => 'content-feature-expires-soon',
                'feature_expires_at' => now()->addDay(),
                'featured_at' => now()->subDays(5),
                'publish_at' => now()->subDays(10),
                'expires_at' => now()->addDays(20),
            ],
            [
                'title' => 'Content Feature Expires Later',
                'slug' => 'content-feature-expires-later',
                'feature_expires_at' => now()->addDays(5),
                'featured_at' => now()->subHour(),
                'publish_at' => now()->subDays(10),
                'expires_at' => now()->addDays(20),
            ],
            [
                'title' => 'Content Featured Recently',
                'slug' => 'content-featured-recently',
                'featured_at' => now()->subHour(),
                'publish_at' => now()->subDays(10),
                'expires_at' => now()->addDays(20),
            ],
            [
                'title' => 'Content Featured Earlier',
                'slug' => 'content-featured-earlier',
                'featured_at' => now()->subDays(2),
                'publish_at' => now()->subHour(),
                'expires_at' => now()->addDays(20),
            ],
            [
                'title' => 'Content Overall Deadline Soon',
                'slug' => 'content-overall-deadline-soon',
                'publish_at' => now()->subDays(10),
                'expires_at' => now()->addDay(),
            ],
            [
                'title' => 'Content Overall Deadline Later',
                'slug' => 'content-overall-deadline-later',
                'publish_at' => now()->subHour(),
                'expires_at' => now()->addDays(5),
            ],
            [
                'title' => 'Content Publish Latest',
                'slug' => 'content-publish-latest',
                'publish_at' => now()->subHour(),
            ],
            [
                'title' => 'Content Publish Older',
                'slug' => 'content-publish-older',
                'publish_at' => now()->subDays(2),
            ],
            [
                'title' => 'Content Alpha Title Tie',
                'slug' => 'content-alpha-title-tie',
                'publish_at' => $samePublishAt,
            ],
            [
                'title' => 'Content Zulu Title Tie',
                'slug' => 'content-zulu-title-tie',
                'publish_at' => $samePublishAt,
            ],
        ] as $announcement) {
            Announcement::query()->create([
                'summary' => $announcement['title'].' summary.',
                'is_featured' => true,
                'is_published' => true,
                ...$announcement,
            ]);
        }

        Page::query()->create([
            'title' => 'Visit',
            'slug' => 'visit',
            'content_blocks' => [
                [
                    'type' => 'announcements_bar',
                    'data' => [
                        'is_visible' => true,
                        'heading' => 'Page News',
                        'background' => 'white',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->assertStringOrder(
            $this->get('/visit')->assertOk()->content(),
            [
                'Content Feature Expires Soon',
                'Content Feature Expires Later',
                'Content Featured Recently',
                'Content Featured Earlier',
                'Content Overall Deadline Soon',
                'Content Overall Deadline Later',
                'Content Publish Latest',
                'Content Publish Older',
                'Content Alpha Title Tie',
                'Content Zulu Title Tie',
            ],
        );
    }

    public function test_embed_blocks_render_raw_provider_code_on_public_pages(): void
    {
        $embedCode = '<div class="ocs-embed" data-ocs-bg="#ffffff" data-ocs-tenant="twyxtco" data-ocs-embed="events/calendar" data-ocs-calendars="1,17,16,2,7,10,6,5"></div>'
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
            ->assertSee('<div class="ocs-embed" data-ocs-bg="#ffffff" data-ocs-tenant="twyxtco" data-ocs-embed="events/calendar" data-ocs-calendars="1,17,16,2,7,10,6,5"></div>', false)
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

    private function assertStringOrder(string $content, array $values): void
    {
        $previousPosition = -1;

        foreach ($values as $value) {
            $position = strpos($content, $value);

            $this->assertNotFalse($position, "Failed asserting that [{$value}] appears in the response.");
            $this->assertGreaterThan($previousPosition, $position, "Failed asserting that [{$value}] appears in the expected order.");

            $previousPosition = $position;
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\FileDocument;
use App\Models\FileDocumentVersion;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\ContentBlocks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

    public function test_sermons_slug_can_be_used_by_a_normal_page(): void
    {
        Page::query()->create([
            'title' => 'Messages',
            'slug' => 'sermons',
            'intro' => 'Current teaching and message links.',
            'body' => 'This is a normal page now.',
            'is_published' => true,
        ]);

        $this->get('/sermons')
            ->assertOk()
            ->assertSee('Messages')
            ->assertSee('Current teaching and message links.')
            ->assertSee('This is a normal page now.')
            ->assertDontSee('Sermons are currently available on YouTube.');
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

    public function test_page_message_renders_in_hero_only_when_present(): void
    {
        Page::query()->create([
            'title' => 'Kids',
            'slug' => 'kids',
            'hero_label' => 'Children & Youth',
            'intro' => 'Sunday morning ministry for children.',
            'message' => "First-time family check-in is available at the Kids desk.\n\nOur team is ready to help.",
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Students',
            'slug' => 'students',
            'intro' => 'Student ministry.',
            'is_published' => true,
        ]);

        $this->get('/kids')
            ->assertOk()
            ->assertSee('page-hero--page-message', false)
            ->assertSee('ministry-hero-contact leadership-hero-contact page-hero-message', false)
            ->assertSee('First-time family check-in is available at the Kids desk.')
            ->assertSee('Our team is ready to help.');

        $this->get('/students')
            ->assertOk()
            ->assertDontSee('page-hero--page-message', false)
            ->assertDontSee('page-hero-message', false);
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

    public function test_child_cards_block_renders_limited_parent_pages_and_public_files(): void
    {
        $parent = Page::query()->create([
            'title' => 'Resources',
            'slug' => 'resources',
            'content_blocks' => [
                [
                    'type' => 'related_content',
                    'data' => [
                        'heading' => 'Featured Resources',
                        'intro' => 'Useful next steps.',
                        'background' => 'teal',
                        'content_type' => 'both',
                        'display_mode' => 'featured',
                        'file_categories' => ['Form'],
                        'item_limit' => 4,
                        'link_label' => 'View all resources',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Baptism',
            'slug' => 'resources/baptism',
            'hero_label' => 'Page',
            'intro' => 'Learn about baptism.',
            'card_image_path' => 'pages/cards/baptism.jpg',
            'sort_order' => 10,
            'featured_at' => now()->subDay(),
            'feature_expires_at' => now()->addDay(),
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Membership',
            'slug' => 'resources/membership',
            'intro' => 'Become a member.',
            'hero_image_path' => 'pages/hero-images/membership.jpg',
            'sort_order' => 20,
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Classes',
            'slug' => 'resources/classes',
            'intro' => 'Find a class.',
            'sort_order' => 30,
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Future Feature',
            'slug' => 'resources/future-feature',
            'featured_at' => now()->addDay(),
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Draft Resource',
            'slug' => 'resources/draft-resource',
            'is_published' => false,
        ]);

        $this->createLiveFileDocument($parent, 'Connection Card', 'connection-card', 'Form', 'Fill this out.');
        $this->createLiveFileDocument($parent, 'Annual Waiver', 'annual-waiver', 'Form', 'Bring this waiver.', cardImagePath: 'file-documents/card-images/annual-waiver.jpg');
        $this->createLiveFileDocument($parent, 'Weekly Bulletin', 'weekly-bulletin', 'Bulletin', 'Filtered by category.');
        $this->createLiveFileDocument($parent, 'Internal Policy', 'internal-policy', 'Form', 'Private file.', visibility: FileDocument::VISIBILITY_PRIVATE);

        $this->get('/resources')
            ->assertOk()
            ->assertSee('concept-updates--bg-teal', false)
            ->assertSee('Featured Resources')
            ->assertSee('Useful next steps.')
            ->assertSee('Baptism')
            ->assertSee('Membership')
            ->assertSee('Classes')
            ->assertSee('Annual Waiver')
            ->assertSee('/storage/pages/cards/baptism.jpg')
            ->assertSee('/storage/pages/hero-images/membership.jpg')
            ->assertSee(ContentBlocks::DEFAULT_PAGE_CARD_IMAGE_PATH)
            ->assertSee('/storage/file-documents/card-images/annual-waiver.jpg')
            ->assertSee('/resources/baptism')
            ->assertSee('/files/annual-waiver')
            ->assertSee('View all resources')
            ->assertSee('/resources/featured-resources')
            ->assertDontSee('Connection Card')
            ->assertDontSee('Weekly Bulletin')
            ->assertDontSee('Internal Policy')
            ->assertDontSee('Future Feature')
            ->assertDontSee('Draft Resource');

        $this->get('/resources/featured-resources')
            ->assertOk()
            ->assertSee('<h1>Featured Resources</h1>', false)
            ->assertSee('Baptism')
            ->assertSee('Membership')
            ->assertSee('Classes')
            ->assertSee('Connection Card')
            ->assertSee('Annual Waiver')
            ->assertSee(ContentBlocks::DEFAULT_PAGE_CARD_IMAGE_PATH)
            ->assertSee(FileDocument::DEFAULT_CARD_IMAGE_PATH)
            ->assertDontSee('Weekly Bulletin')
            ->assertDontSee('Internal Policy')
            ->assertDontSee('Future Feature')
            ->assertDontSee('Draft Resource');
    }

    public function test_related_content_block_defaults_to_child_cards_label_and_slug(): void
    {
        $parent = Page::query()->create([
            'title' => 'Resources',
            'slug' => 'resources',
            'content_blocks' => [
                [
                    'type' => 'related_content',
                    'data' => [
                        'item_limit' => 1,
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'First Child',
            'slug' => 'resources/first-child',
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Second Child',
            'slug' => 'resources/second-child',
            'is_published' => true,
        ]);

        $this->get('/resources')
            ->assertOk()
            ->assertSee('Child Cards')
            ->assertSee('/resources/child-cards')
            ->assertDontSee('Related Content');

        $this->get('/resources/child-cards')
            ->assertOk()
            ->assertSee('<h1>Child Cards</h1>', false)
            ->assertSee('First Child')
            ->assertSee('Second Child')
            ->assertDontSee('Related Content');
    }

    public function test_child_cards_block_can_render_all_files_without_child_pages(): void
    {
        $parent = Page::query()->create([
            'title' => 'Resources',
            'slug' => 'resources',
            'body' => 'Parent page body.',
            'content_blocks' => [
                [
                    'type' => 'related_content',
                    'data' => [
                        'heading' => 'File Cards',
                        'content_type' => 'files',
                        'item_limit' => 4,
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->createLiveFileDocument($parent, 'Connection Card', 'connection-card', 'Form', 'Fill this out.');

        $this->get('/resources')
            ->assertOk()
            ->assertSee('File Cards')
            ->assertSee('Connection Card')
            ->assertSee(FileDocument::DEFAULT_CARD_IMAGE_PATH)
            ->assertDontSee('Related Content');
    }

    public function test_related_content_block_does_not_render_without_child_or_file_content(): void
    {
        Page::query()->create([
            'title' => 'Resources',
            'slug' => 'resources',
            'body' => 'Parent page body.',
            'content_blocks' => [
                [
                    'type' => 'related_content',
                    'data' => [
                        'heading' => 'Child Cards',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/resources')
            ->assertOk()
            ->assertSee('Parent page body.')
            ->assertDontSee('Child Cards')
            ->assertDontSee('Related Content');

        $this->get('/resources/child-cards')->assertNotFound();
    }

    public function test_real_page_slug_takes_priority_over_related_content_listing_slug(): void
    {
        $parent = Page::query()->create([
            'title' => 'Resources',
            'slug' => 'resources',
            'content_blocks' => [
                [
                    'type' => 'related_content',
                    'data' => [
                        'heading' => 'Bulletins',
                        'listing_slug' => 'bulletins',
                        'item_limit' => 1,
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Real Bulletins Page',
            'slug' => 'resources/bulletins',
            'body' => 'This real page should win.',
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'First Child',
            'slug' => 'resources/first-child',
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Second Child',
            'slug' => 'resources/second-child',
            'is_published' => true,
        ]);

        $this->get('/resources')
            ->assertOk()
            ->assertSee('Bulletins')
            ->assertDontSee('/resources/bulletins');

        $this->get('/resources/bulletins')
            ->assertOk()
            ->assertSee('Real Bulletins Page')
            ->assertSee('This real page should win.')
            ->assertDontSee('First Child')
            ->assertDontSee('Second Child');
    }

    public function test_youtube_feed_block_renders_recent_videos_on_public_pages(): void
    {
        $feedUrl = 'https://example.com/page-youtube-feed.xml';

        Cache::forget($this->youtubeCacheKey($feedUrl, 12));

        Http::fake([
            $feedUrl => Http::response($this->youtubeFeed(), 200, [
                'Content-Type' => 'application/xml',
            ]),
        ]);

        Page::query()->create([
            'title' => 'Messages',
            'slug' => 'messages',
            'content_blocks' => [
                [
                    'type' => 'youtube_feed',
                    'data' => [
                        'youtube_channel_url' => 'https://www.youtube.com/@gfreesermons9521',
                        'youtube_feed_url' => $feedUrl,
                        'youtube_link_label' => 'View more on YouTube',
                        'item_limit' => 12,
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/messages')
            ->assertOk()
            ->assertSee('sermon-index', false)
            ->assertSee('sermon-grid', false)
            ->assertSee('Latest Teaching Video')
            ->assertSee('https://i2.ytimg.com/vi/pagevideo123/hqdefault.jpg')
            ->assertSee('https://www.youtube.com/watch?v=pagevideo123')
            ->assertSee('Jun 2, 2026')
            ->assertSee('A page block video description.')
            ->assertSee('https://www.youtube.com/@gfreesermons9521/videos')
            ->assertSee('View more on YouTube')
            ->assertDontSee('Videos are currently available on YouTube.');
    }

    public function test_youtube_feed_block_falls_back_to_channel_link_when_feed_fails(): void
    {
        $feedUrl = 'https://example.com/page-youtube-feed-failing.xml';

        Cache::forget($this->youtubeCacheKey($feedUrl, 12));

        Http::fake([
            $feedUrl => Http::response('', 500),
        ]);

        Page::query()->create([
            'title' => 'Messages',
            'slug' => 'messages',
            'content_blocks' => [
                [
                    'type' => 'youtube_feed',
                    'data' => [
                        'youtube_channel_url' => 'https://www.youtube.com/@gfreesermons9521',
                        'youtube_feed_url' => $feedUrl,
                        'youtube_link_label' => 'Watch more on YouTube',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/messages')
            ->assertOk()
            ->assertSee('Videos are currently available on YouTube.')
            ->assertSee('https://www.youtube.com/@gfreesermons9521/videos')
            ->assertSee('Watch more on YouTube')
            ->assertDontSee('sermon-grid', false);
    }

    public function test_youtube_feed_block_does_not_render_without_a_source(): void
    {
        Page::query()->create([
            'title' => 'Messages',
            'slug' => 'messages',
            'body' => 'Messages page body.',
            'content_blocks' => [
                [
                    'type' => 'youtube_feed',
                    'data' => [
                        'youtube_link_label' => 'View more on YouTube',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/messages')
            ->assertOk()
            ->assertSee('Messages page body.')
            ->assertDontSee('sermon-index', false)
            ->assertDontSee('View more on YouTube')
            ->assertDontSee('Videos are currently available on YouTube.');
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

    private function youtubeFeed(): string
    {
        return <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <feed xmlns:yt="http://www.youtube.com/xml/schemas/2015" xmlns:media="http://search.yahoo.com/mrss/" xmlns="http://www.w3.org/2005/Atom">
            <entry>
                <id>yt:video:pagevideo123</id>
                <yt:videoId>pagevideo123</yt:videoId>
                <title>Latest Teaching Video</title>
                <link rel="alternate" href="https://www.youtube.com/watch?v=pagevideo123"/>
                <published>2026-06-02T12:00:00+00:00</published>
                <media:group>
                    <media:title>Latest Teaching Video</media:title>
                    <media:thumbnail url="https://i2.ytimg.com/vi/pagevideo123/hqdefault.jpg" width="480" height="360"/>
                    <media:description>A page block video description.</media:description>
                </media:group>
            </entry>
        </feed>
        XML;
    }

    private function youtubeCacheKey(string $feedUrl, int $limit): string
    {
        return 'youtube-sermons-feed-v3-'.sha1($feedUrl)."-{$limit}";
    }

    private function createLiveFileDocument(
        Page $parent,
        string $title,
        string $fileName,
        string $category,
        string $description,
        string $visibility = FileDocument::VISIBILITY_PUBLIC,
        ?string $cardImagePath = null,
    ): FileDocument {
        $document = FileDocument::query()->create([
            'parent_page_id' => $parent->getKey(),
            'card_image_path' => $cardImagePath,
            'title' => $title,
            'file_name' => $fileName,
            'category' => $category,
            'description' => $description,
            'visibility' => $visibility,
            'is_published' => true,
        ]);

        $version = FileDocumentVersion::query()->create([
            'file_document_id' => $document->getKey(),
            'disk' => 'local',
            'path' => "documents/{$fileName}.pdf",
            'original_name' => "{$fileName}.pdf",
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'size' => 1000,
        ]);

        $document->update(['current_version_id' => $version->getKey()]);

        return $document->refresh();
    }
}

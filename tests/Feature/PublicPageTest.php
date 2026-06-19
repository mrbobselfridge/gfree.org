<?php

namespace Tests\Feature;

use App\Models\FileDocument;
use App\Models\FileDocumentVersion;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\ContentBlocks;
use App\Support\SiteDesignPalette;
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

    public function test_page_noindex_nofollow_toggle_outputs_robots_meta_tag(): void
    {
        Page::query()->create([
            'title' => 'Private Landing Page',
            'slug' => 'private-landing-page',
            'noindex_nofollow' => true,
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Indexed Page',
            'slug' => 'indexed-page',
            'is_published' => true,
        ]);

        $this->get('/private-landing-page')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $this->get('/indexed-page')
            ->assertOk()
            ->assertDontSee('<meta name="robots" content="noindex, nofollow">', false);
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

    public function test_pages_without_header_images_use_site_default_page_header_image(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'default_page_header_image_path' => 'site-settings/page-header-images/default-banner.jpg',
        ]);

        Page::query()->create([
            'title' => 'Resources',
            'slug' => 'resources',
            'intro' => 'Useful links and forms.',
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Serve',
            'slug' => 'serve',
            'intro' => 'Find a team.',
            'hero_image_path' => 'pages/hero-images/serve.jpg',
            'is_published' => true,
        ]);

        $this->get('/resources')
            ->assertOk()
            ->assertSee('page-hero--image', false)
            ->assertSee('/storage/site-settings/page-header-images/default-banner.jpg', false);

        $this->get('/serve')
            ->assertOk()
            ->assertSee('/storage/pages/hero-images/serve.jpg', false)
            ->assertDontSee('/storage/site-settings/page-header-images/default-banner.jpg', false);
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
            ->assertSee('ministry-hero-contact page-hero-message', false)
            ->assertSee('First-time family check-in is available at the Kids desk.')
            ->assertSee('Our team is ready to help.');

        $this->get('/students')
            ->assertOk()
            ->assertDontSee('page-hero--page-message', false)
            ->assertDontSee('page-hero-message', false);
    }

    public function test_page_message_can_render_rich_html_in_hero(): void
    {
        Page::query()->create([
            'title' => 'Leadership',
            'slug' => 'leadership-message',
            'message' => '<div class="custom-callout" style="color: #123456"><h3>Leadership Contact Info</h3><p><strong>Pastor Noel Meyers</strong></p><ul><li>Available Tuesday</li></ul><p><a href="mailto:thenoel@gfree.org">thenoel@gfree.org</a></p></div>',
            'is_published' => true,
        ]);

        $this->get('/leadership-message')
            ->assertOk()
            ->assertSee('<div class="custom-callout" style="color: #123456">', false)
            ->assertSee('<h3>Leadership Contact Info</h3>', false)
            ->assertSee('<strong>Pastor Noel Meyers</strong>', false)
            ->assertSee('<ul><li>Available Tuesday</li></ul>', false)
            ->assertSee('<a href="mailto:thenoel@gfree.org">thenoel@gfree.org</a>', false)
            ->assertDontSee('&lt;strong&gt;', false);
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

    public function test_content_blocks_render_custom_managed_background_color(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'design_background_colors' => [
                ...SiteDesignPalette::defaultBackgroundColors(),
                [
                    'key' => 'midnight-blue',
                    'name' => 'Midnight Blue',
                    'hex' => '#102030',
                ],
            ],
        ]);

        Page::query()->create([
            'title' => 'Custom Color',
            'slug' => 'custom-color',
            'content_blocks' => [
                [
                    'type' => 'text',
                    'data' => [
                        'heading' => 'Custom background',
                        'body' => '<p>Palette driven section.</p>',
                        'background' => 'midnight-blue',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/custom-color')
            ->assertOk()
            ->assertSee('page-block--bg-midnight-blue', false)
            ->assertSee('--page-block-bg: #102030', false)
            ->assertSee('--page-block-fg: #ffffff', false)
            ->assertSee('Palette driven section.');
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

    public function test_image_text_blocks_support_top_and_bottom_positions_with_content_widths(): void
    {
        Page::query()->create([
            'title' => 'Image Layouts',
            'slug' => 'image-layouts',
            'content_blocks' => [
                [
                    'type' => 'image_text',
                    'data' => [
                        'image_path' => 'pages/content-images/top.jpg',
                        'image_alt' => 'Top image',
                        'heading' => 'Image above content',
                        'body' => '<p>Text below the image.</p>',
                        'image_position' => 'top',
                        'content_width' => 'small',
                        'background' => 'white',
                    ],
                ],
                [
                    'type' => 'image_text',
                    'data' => [
                        'image_path' => 'pages/content-images/bottom.jpg',
                        'image_alt' => 'Bottom image',
                        'heading' => 'Image below content',
                        'body' => '<p>Text above the image.</p>',
                        'image_position' => 'bottom',
                        'content_width' => 'medium',
                        'background' => 'white',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/image-layouts')
            ->assertOk()
            ->assertSee('page-block--image-top', false)
            ->assertSee('page-block--image-bottom', false)
            ->assertSee('page-block__inner--text-small', false)
            ->assertSee('page-block__inner--text-medium', false)
            ->assertSee('Image above content')
            ->assertSee('Image below content');
    }

    public function test_remaining_content_block_types_render_content_width_classes(): void
    {
        $parent = Page::query()->create([
            'title' => 'Resource Hub',
            'slug' => 'resource-hub',
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Child Resource',
            'slug' => 'resource-hub/child-resource',
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Widths',
            'slug' => 'widths',
            'content_blocks' => [
                [
                    'type' => 'process_steps',
                    'data' => [
                        'heading' => 'Small process',
                        'content_width' => 'small',
                        'steps' => [
                            ['title' => 'Step one', 'summary' => 'Do this first.'],
                        ],
                    ],
                ],
                [
                    'type' => 'link_cards',
                    'data' => [
                        'heading' => 'Medium cards',
                        'content_width' => 'medium',
                        'cards' => [
                            ['title' => 'Card one', 'summary' => 'A card summary.'],
                        ],
                    ],
                ],
                [
                    'type' => 'embed',
                    'data' => [
                        'heading' => 'Small embed',
                        'content_width' => 'small',
                        'embed_code' => '<iframe src="https://example.com/embed"></iframe>',
                    ],
                ],
                [
                    'type' => 'info_strip',
                    'data' => [
                        'spacing' => 'none',
                        'content_width' => 'medium',
                        'items' => [
                            ['label' => 'Office', 'source' => 'custom', 'value' => 'Open today'],
                        ],
                    ],
                ],
                [
                    'type' => 'related_content',
                    'data' => [
                        'associated_parent_page_id' => $parent->getKey(),
                        'heading' => 'Small listing',
                        'content_width' => 'small',
                        'content_type' => ContentBlocks::RELATED_CONTENT_TYPE_PAGES,
                        'display_mode' => ContentBlocks::RELATED_CONTENT_MODE_ALL,
                        'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_TITLE_ASC,
                        'item_limit' => 6,
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/widths')
            ->assertOk()
            ->assertSee('page-block__inner--text-small', false)
            ->assertSee('page-block__inner--text-medium', false)
            ->assertSee('page-block--info-strip-width-medium', false)
            ->assertSee('concept-updates--width-small', false)
            ->assertSee('Child Resource');
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

        $this->createLiveFileDocument($parent, 'Connection Card', 'connection-card', 'Form', 'Fill this out.', sortOrder: 50);
        $this->createLiveFileDocument($parent, 'Annual Waiver', 'annual-waiver', 'Form', 'Bring this waiver.', cardImagePath: 'file-documents/card-images/annual-waiver.jpg', sortOrder: 40);
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
            ->assertSee('data-related-load-more', false)
            ->assertSee('data-related-load-more-item', false)
            ->assertSee('Load more')
            ->assertSee('Connection Card')
            ->assertDontSee('Weekly Bulletin')
            ->assertDontSee('Internal Policy')
            ->assertDontSee('Future Feature')
            ->assertDontSee('Draft Resource');

        $this->get('/resources/featured-resources')
            ->assertNotFound();
    }

    public function test_child_cards_block_orders_child_pages_before_randomizing_matching_order_groups(): void
    {
        $parent = Page::query()->create([
            'title' => 'Resources',
            'slug' => 'resources',
            'content_blocks' => [
                [
                    'type' => 'related_content',
                    'data' => [
                        'heading' => 'Child Cards',
                        'content_type' => 'pages',
                        'item_limit' => 10,
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Alpha Later',
            'slug' => 'resources/alpha-later',
            'sort_order' => 20,
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Zed Top Group',
            'slug' => 'resources/zed-top-group',
            'sort_order' => 10,
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Beta Top Group',
            'slug' => 'resources/beta-top-group',
            'sort_order' => 10,
            'is_published' => true,
        ]);

        $content = $this->get('/resources')
            ->assertOk()
            ->assertSee('Child Cards')
            ->assertSee('Alpha Later')
            ->assertSee('Zed Top Group')
            ->assertSee('Beta Top Group')
            ->getContent();

        $laterPosition = strpos($content, 'Alpha Later');

        $this->assertLessThan($laterPosition, strpos($content, 'Zed Top Group'));
        $this->assertLessThan($laterPosition, strpos($content, 'Beta Top Group'));
    }

    public function test_child_cards_block_sorts_mixed_pages_and_files_by_order(): void
    {
        $parent = $this->createRelatedContentParent([
            'content_type' => 'both',
            'display_mode' => ContentBlocks::RELATED_CONTENT_MODE_ALL,
            'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_ORDER_RANDOM,
            'item_limit' => 10,
        ]);

        $this->createLiveFileDocument($parent, 'File First', 'file-first', 'Form', 'First file.', sortOrder: 5);
        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Page Second',
            'slug' => 'resources/page-second',
            'sort_order' => 10,
            'is_published' => true,
        ]);
        $this->createLiveFileDocument($parent, 'File Third', 'file-third', 'Form', 'Third file.', sortOrder: 15);

        $this->get('/resources')
            ->assertOk()
            ->assertSeeInOrder([
                'File First',
                'Page Second',
                'File Third',
            ]);
    }

    public function test_child_cards_block_sorts_by_featured_and_published_dates(): void
    {
        $parent = $this->createRelatedContentParent([
            'display_mode' => ContentBlocks::RELATED_CONTENT_MODE_ALL,
            'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_FEATURED_PUBLISHED_ORDER_RANDOM,
            'item_limit' => 10,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Published Newer No Featured',
            'slug' => 'resources/published-newer-no-featured',
            'sort_order' => 1,
            'publish_at' => '2026-06-05 09:00:00',
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Featured Older',
            'slug' => 'resources/featured-older',
            'sort_order' => 20,
            'featured_at' => '2026-06-02 09:00:00',
            'publish_at' => '2026-06-04 09:00:00',
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Featured Newer',
            'slug' => 'resources/featured-newer',
            'sort_order' => 30,
            'featured_at' => '2026-06-03 09:00:00',
            'publish_at' => '2026-06-01 09:00:00',
            'is_published' => true,
        ]);

        $this->get('/resources')
            ->assertOk()
            ->assertSeeInOrder([
                'Featured Newer',
                'Featured Older',
                'Published Newer No Featured',
            ]);

        $parent->update([
            'content_blocks' => [
                $this->relatedContentBlock([
                    'display_mode' => ContentBlocks::RELATED_CONTENT_MODE_ALL,
                    'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_PUBLISHED_ORDER_RANDOM,
                    'item_limit' => 10,
                ]),
            ],
        ]);

        $this->get('/resources')
            ->assertOk()
            ->assertSeeInOrder([
                'Published Newer No Featured',
                'Featured Older',
                'Featured Newer',
            ]);
    }

    public function test_child_cards_block_sorts_by_title_options(): void
    {
        $parent = $this->createRelatedContentParent([
            'display_mode' => ContentBlocks::RELATED_CONTENT_MODE_ALL,
            'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_TITLE_ASC,
            'item_limit' => 10,
        ]);

        foreach (['Bravo Title', 'Alpha Title', 'Charlie Title'] as $title) {
            Page::query()->create([
                'parent_page_id' => $parent->getKey(),
                'title' => $title,
                'slug' => 'resources/'.(string) str($title)->slug(),
                'is_published' => true,
            ]);
        }

        $this->get('/resources')
            ->assertOk()
            ->assertSeeInOrder([
                'Alpha Title',
                'Bravo Title',
                'Charlie Title',
            ]);

        $parent->update([
            'content_blocks' => [
                $this->relatedContentBlock([
                    'display_mode' => ContentBlocks::RELATED_CONTENT_MODE_ALL,
                    'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_TITLE_DESC,
                    'item_limit' => 10,
                ]),
            ],
        ]);

        $this->get('/resources')
            ->assertOk()
            ->assertSeeInOrder([
                'Charlie Title',
                'Bravo Title',
                'Alpha Title',
            ]);
    }

    public function test_child_cards_block_sorts_by_updated_and_created_options(): void
    {
        $parent = $this->createRelatedContentParent([
            'display_mode' => ContentBlocks::RELATED_CONTENT_MODE_ALL,
            'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_UPDATED_DESC,
            'item_limit' => 10,
        ]);

        $oldCreatedNewUpdated = Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Old Created New Updated',
            'slug' => 'resources/old-created-new-updated',
            'is_published' => true,
        ]);

        $middleCreatedMiddleUpdated = Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Middle Created Middle Updated',
            'slug' => 'resources/middle-created-middle-updated',
            'is_published' => true,
        ]);

        $newCreatedOldUpdated = Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'New Created Old Updated',
            'slug' => 'resources/new-created-old-updated',
            'is_published' => true,
        ]);

        Page::withoutTimestamps(function () use ($oldCreatedNewUpdated, $middleCreatedMiddleUpdated, $newCreatedOldUpdated): void {
            $oldCreatedNewUpdated->forceFill([
                'created_at' => '2026-06-01 09:00:00',
                'updated_at' => '2026-06-05 09:00:00',
            ])->save();

            $middleCreatedMiddleUpdated->forceFill([
                'created_at' => '2026-06-02 09:00:00',
                'updated_at' => '2026-06-04 09:00:00',
            ])->save();

            $newCreatedOldUpdated->forceFill([
                'created_at' => '2026-06-03 09:00:00',
                'updated_at' => '2026-06-03 09:00:00',
            ])->save();
        });

        $this->get('/resources')
            ->assertOk()
            ->assertSeeInOrder([
                'Old Created New Updated',
                'Middle Created Middle Updated',
                'New Created Old Updated',
            ]);

        $parent->update([
            'content_blocks' => [
                $this->relatedContentBlock([
                    'display_mode' => ContentBlocks::RELATED_CONTENT_MODE_ALL,
                    'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_CREATED_DESC,
                    'item_limit' => 10,
                ]),
            ],
        ]);

        $this->get('/resources')
            ->assertOk()
            ->assertSeeInOrder([
                'New Created Old Updated',
                'Middle Created Middle Updated',
                'Old Created New Updated',
            ]);

        $parent->update([
            'content_blocks' => [
                $this->relatedContentBlock([
                    'display_mode' => ContentBlocks::RELATED_CONTENT_MODE_ALL,
                    'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_CREATED_ASC,
                    'item_limit' => 10,
                ]),
            ],
        ]);

        $this->get('/resources')
            ->assertOk()
            ->assertSeeInOrder([
                'Old Created New Updated',
                'Middle Created Middle Updated',
                'New Created Old Updated',
            ]);
    }

    public function test_legacy_newest_child_cards_mode_maps_to_published_sorting(): void
    {
        $parent = $this->createRelatedContentParent([
            'display_mode' => ContentBlocks::RELATED_CONTENT_MODE_NEWEST,
            'sort_preset' => null,
            'item_limit' => 10,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Older Published',
            'slug' => 'resources/older-published',
            'publish_at' => '2026-06-01 09:00:00',
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Newer Published',
            'slug' => 'resources/newer-published',
            'publish_at' => '2026-06-02 09:00:00',
            'is_published' => true,
        ]);

        $this->get('/resources')
            ->assertOk()
            ->assertSeeInOrder([
                'Newer Published',
                'Older Published',
            ]);
    }

    public function test_related_content_block_with_empty_heading_keeps_child_cards_slug_without_displaying_default_label(): void
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
            ->assertSee('data-related-load-more', false)
            ->assertSee('data-related-load-more-item', false)
            ->assertSee('Load more')
            ->assertSee('First Child')
            ->assertSee('Second Child')
            ->assertDontSee('/resources/child-cards')
            ->assertDontSee('Child Cards')
            ->assertDontSee('Related Content');

        $this->get('/resources/child-cards')
            ->assertNotFound();
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
            ->assertSee('class="concept-updates__media-link" href="http://127.0.0.1:8000/files/connection-card" aria-label="Download Connection Card"', false)
            ->assertDontSee('aria-label="Open Connection Card"', false)
            ->assertDontSee('Related Content');
    }

    public function test_file_child_card_can_open_long_optional_content_in_modal(): void
    {
        $parent = Page::query()->create([
            'title' => 'Resources',
            'slug' => 'resources',
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

        $longContent = '<h1 class="text-7xl" style="font-size: 90px">What’s Happening</h1><p>'.str_repeat('Bring this form to the welcome desk after worship. ', 8).'</p><ul><li><strong style="color: teal">Include your contact information.</strong></li><li>Choose the ministry you want to hear from.</li></ul><p><a class="button" style="color: red" href="/connect">Connection form</a></p>';

        $this->createLiveFileDocument(
            parent: $parent,
            title: 'Connection Card',
            fileName: 'connection-card',
            category: 'Form',
            description: 'Fill this out.',
            content: $longContent,
        );

        $this->get('/resources')
            ->assertOk()
            ->assertSee('data-related-modal-open', false)
            ->assertSee('data-related-modal', false)
            ->assertSee('class="concept-updates__modal"', false)
            ->assertSee('More')
            ->assertSee('Close')
            ->assertSee('Include your contact information.')
            ->assertSee('Choose the ministry you want to hear from.')
            ->assertSee('<h1>What’s Happening</h1>', false)
            ->assertSee('<a href="/connect">Connection form</a>', false)
            ->assertDontSee('<h3>Connection Card</h3>', false)
            ->assertDontSee('font-size: 90px', false)
            ->assertDontSee('class="text-7xl"', false)
            ->assertDontSee('style="color: teal"', false);
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

    public function test_related_content_without_layout_defaults_to_card_grid(): void
    {
        $parent = $this->createRelatedContentParent([
            'item_limit' => 1,
            'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_TITLE_ASC,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Alpha Child',
            'slug' => 'resources/alpha-child',
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Beta Child',
            'slug' => 'resources/beta-child',
            'is_published' => true,
        ]);

        $this->get('/resources')
            ->assertOk()
            ->assertSee('concept-updates--layout-card_grid', false)
            ->assertSee('class="concept-updates__grid"', false)
            ->assertSee('data-related-load-more', false)
            ->assertSee('Alpha Child')
            ->assertSee('Beta Child');
    }

    public function test_related_content_carousel_layout_caps_total_items_and_uses_carousel_markup(): void
    {
        $parent = $this->createRelatedContentParent([
            'layout' => ContentBlocks::RELATED_CONTENT_LAYOUT_CARD_CAROUSEL,
            'enable_search' => false,
            'item_limit' => 4,
            'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_TITLE_ASC,
        ]);

        foreach (['Alpha Child', 'Beta Child', 'Delta Child', 'Epsilon Child', 'Gamma Child'] as $title) {
            Page::query()->create([
                'parent_page_id' => $parent->getKey(),
                'title' => $title,
                'slug' => 'resources/'.(string) str($title)->slug(),
                'intro' => "{$title} summary.",
                'is_published' => true,
            ]);
        }

        $this->get('/resources')
            ->assertOk()
            ->assertSee('concept-updates--layout-card_carousel', false)
            ->assertSee('data-related-carousel', false)
            ->assertSee('data-related-carousel-previous', false)
            ->assertSee('data-related-carousel-next', false)
            ->assertDontSee('data-related-load-more', false)
            ->assertSee('Alpha Child')
            ->assertSee('Beta Child')
            ->assertSee('Delta Child')
            ->assertSee('Epsilon Child')
            ->assertDontSee('Gamma Child');
    }

    public function test_related_content_search_enabled_carousel_renders_items_beyond_limit(): void
    {
        $parent = $this->createRelatedContentParent([
            'layout' => ContentBlocks::RELATED_CONTENT_LAYOUT_CARD_CAROUSEL,
            'item_limit' => 2,
            'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_TITLE_ASC,
        ]);

        foreach (['Alpha Child', 'Beta Child', 'Gamma Child'] as $title) {
            Page::query()->create([
                'parent_page_id' => $parent->getKey(),
                'title' => $title,
                'slug' => 'resources/'.(string) str($title)->slug(),
                'intro' => "{$title} summary.",
                'is_published' => true,
            ]);
        }

        $this->get('/resources')
            ->assertOk()
            ->assertSee('data-related-search-section', false)
            ->assertSee('data-related-search-input', false)
            ->assertSee('data-related-carousel', false)
            ->assertSee('Alpha Child')
            ->assertSee('Beta Child')
            ->assertSee('Gamma Child');
    }

    public function test_related_content_carousel_hides_arrows_when_items_do_not_exceed_limit(): void
    {
        $parent = $this->createRelatedContentParent([
            'layout' => ContentBlocks::RELATED_CONTENT_LAYOUT_CARD_CAROUSEL,
            'item_limit' => 3,
            'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_TITLE_ASC,
        ]);

        foreach (['Alpha Child', 'Beta Child', 'Gamma Child'] as $title) {
            Page::query()->create([
                'parent_page_id' => $parent->getKey(),
                'title' => $title,
                'slug' => 'resources/'.(string) str($title)->slug(),
                'intro' => "{$title} summary.",
                'is_published' => true,
            ]);
        }

        $this->get('/resources')
            ->assertOk()
            ->assertSee('data-related-carousel', false)
            ->assertDontSee('data-related-carousel-previous', false)
            ->assertDontSee('data-related-carousel-next', false)
            ->assertSee('Alpha Child')
            ->assertSee('Beta Child')
            ->assertSee('Gamma Child');
    }

    public function test_related_content_label_list_layout_loads_more_items_and_shows_summaries(): void
    {
        $parent = $this->createRelatedContentParent([
            'layout' => ContentBlocks::RELATED_CONTENT_LAYOUT_BULLET_LIST,
            'item_limit' => 2,
            'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_TITLE_ASC,
        ]);

        foreach (['Alpha Child', 'Beta Child', 'Gamma Child'] as $title) {
            Page::query()->create([
                'parent_page_id' => $parent->getKey(),
                'title' => $title,
                'slug' => 'resources/'.str($title)->slug(),
                'hero_label' => "{$title} Label",
                'intro' => "{$title} summary.",
                'message' => "{$title} message.",
                'card_image_path' => 'pages/cards/'.(string) str($title)->slug().'.jpg',
                'is_published' => true,
            ]);
        }

        $this->get('/resources')
            ->assertOk()
            ->assertSee('concept-updates--layout-bullet_list', false)
            ->assertSee('class="concept-updates__bullet-list"', false)
            ->assertSee('class="concept-updates__bullet-media"', false)
            ->assertSee('class="concept-updates__bullet-title"', false)
            ->assertSee('class="concept-updates__bullet-label"', false)
            ->assertSee('class="concept-updates__bullet-message"', false)
            ->assertSee('data-related-load-more', false)
            ->assertSee('data-related-load-more-item', false)
            ->assertSee('Load more')
            ->assertDontSee('class="concept-updates__grid"', false)
            ->assertDontSee('data-related-carousel', false)
            ->assertSee('/storage/pages/cards/alpha-child.jpg', false)
            ->assertSee('Alpha Child Label')
            ->assertSee('Alpha Child')
            ->assertSee('Alpha Child summary.')
            ->assertSee('Alpha Child message.')
            ->assertSee('/storage/pages/cards/beta-child.jpg', false)
            ->assertSee('Beta Child Label')
            ->assertSee('Beta Child')
            ->assertSee('Beta Child summary.')
            ->assertSee('Beta Child message.')
            ->assertSee('Gamma Child')
            ->assertSee('Gamma Child Label')
            ->assertSee('Gamma Child message.');
    }

    public function test_related_content_search_renders_search_box_and_searchable_metadata(): void
    {
        $parent = $this->createRelatedContentParent([
            'content_type' => ContentBlocks::RELATED_CONTENT_TYPE_BOTH,
            'display_mode' => ContentBlocks::RELATED_CONTENT_MODE_ALL,
            'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_TITLE_ASC,
            'item_limit' => 1,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Alpha Child',
            'slug' => 'resources/alpha-child',
            'hero_label' => 'Ministry',
            'intro' => 'Alpha child intro.',
            'message' => '<p>Prayer team welcome details.</p>',
            'is_published' => true,
        ]);

        $file = $this->createLiveFileDocument(
            parent: $parent,
            title: 'Connection Packet',
            fileName: 'connection-packet',
            category: 'Form',
            description: 'Download this welcome PDF.',
            content: '<p>Hidden file covenant content.</p>',
        );
        $file->update(['tags' => ['downloadable-tag']]);

        $this->get('/resources')
            ->assertOk()
            ->assertSee('data-related-search-section', false)
            ->assertSee('data-related-search-input', false)
            ->assertSee('data-related-search-listing', false)
            ->assertSee('data-related-search-item', false)
            ->assertSee('No matching items.')
            ->assertSee('Alpha Child')
            ->assertSee('Ministry')
            ->assertSee('Alpha child intro.')
            ->assertSee('Prayer team welcome details.')
            ->assertSee('Connection Packet')
            ->assertSee('Form')
            ->assertSee('connection-packet')
            ->assertSee('Download this welcome PDF.')
            ->assertSee('downloadable-tag')
            ->assertSee('Hidden file covenant content.');
    }

    public function test_related_content_search_can_be_disabled(): void
    {
        $parent = $this->createRelatedContentParent([
            'enable_search' => false,
            'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_TITLE_ASC,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Alpha Child',
            'slug' => 'resources/alpha-child',
            'is_published' => true,
        ]);

        $this->get('/resources')
            ->assertOk()
            ->assertDontSee('data-related-search-section', false)
            ->assertDontSee('data-related-search-input', false)
            ->assertDontSee('data-related-search-listing', false)
            ->assertDontSee('data-related-search-item', false);
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
                        'content_width' => 'medium',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $this->get('/messages')
            ->assertOk()
            ->assertSee('sermon-index', false)
            ->assertSee('sermon-grid', false)
            ->assertSee('page-block__inner--text-medium', false)
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

    private function createRelatedContentParent(array $data = []): Page
    {
        return Page::query()->create([
            'title' => 'Resources',
            'slug' => 'resources',
            'content_blocks' => [
                $this->relatedContentBlock($data),
            ],
            'is_published' => true,
        ]);
    }

    private function relatedContentBlock(array $data = []): array
    {
        return [
            'type' => 'related_content',
            'data' => array_merge([
                'heading' => 'Child Cards',
                'content_type' => ContentBlocks::RELATED_CONTENT_TYPE_PAGES,
                'display_mode' => ContentBlocks::RELATED_CONTENT_MODE_FEATURED,
                'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_ORDER_RANDOM,
                'item_limit' => ContentBlocks::RELATED_CONTENT_DEFAULT_LIMIT,
            ], $data),
        ];
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
        int $sortOrder = 0,
        ?string $content = null,
    ): FileDocument {
        $document = FileDocument::query()->create([
            'parent_page_id' => $parent->getKey(),
            'card_image_path' => $cardImagePath,
            'sort_order' => $sortOrder,
            'title' => $title,
            'file_name' => $fileName,
            'category' => $category,
            'description' => $description,
            'content' => $content,
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

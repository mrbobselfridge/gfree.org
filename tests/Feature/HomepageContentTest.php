<?php

namespace Tests\Feature;

use App\Models\HomepageContent;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HomepageContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_content_overrides_static_homepage_sections(): void
    {
        HomepageContent::query()->create([
            'content_blocks' => [
                [
                    'type' => 'text',
                    'data' => [
                        'eyebrow' => 'First Look',
                        'heading' => 'A clearer way to start.',
                        'body' => '<p>Everything a guest needs is right here.</p>',
                        'background' => 'white',
                    ],
                ],
                [
                    'type' => 'process_steps',
                    'data' => [
                        'eyebrow' => 'Serve Here',
                        'heading' => 'Find your serving lane.',
                        'background' => 'forest',
                        'steps' => [
                            ['title' => 'Say hello', 'summary' => 'Tell us where you want to help.'],
                            ['title' => 'Meet a leader', 'summary' => 'We will make the next step clear.'],
                        ],
                    ],
                ],
                [
                    'type' => 'image_text',
                    'data' => [
                        'eyebrow' => 'Connect',
                        'heading' => 'Use One Church for forms.',
                        'body' => '<p>Registrations and giving live in one place.</p>',
                        'button_label' => 'Open Forms',
                        'button_url' => '/forms',
                        'background' => 'black',
                        'image_position' => 'right',
                    ],
                ],
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('First Look')
            ->assertSee('A clearer way to start.')
            ->assertSee('Everything a guest needs is right here.')
            ->assertSee('Serve Here')
            ->assertSee('Find your serving lane.')
            ->assertSee('Say hello')
            ->assertSee('Meet a leader')
            ->assertSee('Connect')
            ->assertSee('Use One Church for forms.')
            ->assertSee('Open Forms')
            ->assertSee('/forms')
            ->assertDontSee('Everything a guest needs without digging.')
            ->assertDontSee('Every step matters.');
    }

    public function test_homepage_renders_default_content_blocks_when_no_record_exists(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('concept-service-strip', false)
            ->assertSee('page-block--info-strip-spacing-bottom', false)
            ->assertSee('--info-strip-count: 3', false)
            ->assertSee('Sunday')
            ->assertSee('9:00 & 10:45 AM', false)
            ->assertSee('Visit')
            ->assertSee('305 Keystone Hill Road')
            ->assertSee('Next Step')
            ->assertSee('Connect Card & Prayer', false)
            ->assertSee('Everything a guest needs without digging.')
            ->assertSee('Visit Sunday')
            ->assertSee('Every step matters.')
            ->assertSee('One Church handles the moving parts.')
            ->assertSee('page-block--process-steps', false);
    }

    public function test_homepage_uses_custom_seo_metadata_when_present(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'tagline' => 'A stable church tagline.',
        ]);

        HomepageContent::query()->create([
            'seo_title' => 'Welcome to gFree',
            'seo_description' => 'A custom homepage description for search and analytics.',
            'content_blocks' => [],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Welcome to gFree</title>', false)
            ->assertSee('<meta name="description" content="A custom homepage description for search and analytics.">', false);
    }

    public function test_homepage_seo_title_defaults_to_church_name_when_blank(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'tagline' => 'A stable church tagline.',
        ]);

        HomepageContent::query()->create([
            'content_blocks' => [],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>gFree Church</title>', false)
            ->assertSee('<meta name="description" content="A stable church tagline.">', false);
    }

    public function test_homepage_content_renders_flexible_content_blocks(): void
    {
        HomepageContent::query()->create([
            'content_blocks' => [
                [
                    'type' => 'text',
                    'data' => [
                        'eyebrow' => 'More Ways',
                        'heading' => 'Life at TwyxtCo',
                        'body' => '<p>Groups, events, and serving opportunities.</p>',
                        'background' => 'forest',
                    ],
                ],
                [
                    'type' => 'process_steps',
                    'data' => [
                        'eyebrow' => 'Path',
                        'heading' => 'Simple process',
                        'background' => 'black',
                        'steps' => [
                            ['title' => 'Choose', 'summary' => 'Pick a first step.'],
                        ],
                    ],
                ],
                [
                    'type' => 'cta',
                    'data' => [
                        'eyebrow' => 'Ready',
                        'heading' => 'Take a next step',
                        'body' => '<p>Start with a <strong>simple</strong> form.</p>',
                        'button_label' => 'Get Started',
                        'button_url' => '/next-step',
                        'background' => 'gold',
                        'layout' => 'button_top',
                    ],
                ],
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('page-block--bg-forest', false)
            ->assertSee('More Ways')
            ->assertSee('Life at TwyxtCo')
            ->assertSee('Groups, events, and serving opportunities.')
            ->assertSee('page-block--process-steps', false)
            ->assertSee('Simple process')
            ->assertSee('Pick a first step.')
            ->assertSee('page-block--bg-gold', false)
            ->assertSee('page-block--cta-button-top', false)
            ->assertSee('page-block__inner--text-medium', false)
            ->assertSee('Take a next step')
            ->assertSee('<strong>simple</strong>', false)
            ->assertSee('Get Started')
            ->assertSee('/next-step');
    }

    public function test_homepage_content_blocks_respect_publish_and_expire_dates(): void
    {
        $this->travelTo(Carbon::parse('2026-06-06 12:00:00'));

        HomepageContent::query()->create([
            'content_blocks' => [
                [
                    'type' => 'text',
                    'data' => [
                        'heading' => 'Always Visible Block',
                        'body' => '<p>This block has no schedule.</p>',
                        'background' => 'white',
                    ],
                ],
                [
                    'type' => 'text',
                    'data' => [
                        'heading' => 'Currently Visible Block',
                        'body' => '<p>This block is inside the schedule window.</p>',
                        'background' => 'white',
                        'publish_at' => '2026-06-06 08:00:00',
                        'expires_at' => '2026-06-06 17:00:00',
                    ],
                ],
                [
                    'type' => 'text',
                    'data' => [
                        'heading' => 'Future Hidden Block',
                        'body' => '<p>This block should not be shown yet.</p>',
                        'background' => 'white',
                        'publish_at' => '2026-06-07 08:00:00',
                    ],
                ],
                [
                    'type' => 'text',
                    'data' => [
                        'heading' => 'Expired Hidden Block',
                        'body' => '<p>This block should no longer be shown.</p>',
                        'background' => 'white',
                        'expires_at' => '2026-06-06 08:00:00',
                    ],
                ],
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Always Visible Block')
            ->assertSee('Currently Visible Block')
            ->assertDontSee('Future Hidden Block')
            ->assertDontSee('Expired Hidden Block');
    }

    public function test_homepage_content_renders_rich_text_embeds(): void
    {
        $embedCode = '<iframe src="https://example.com/form"></iframe>';

        HomepageContent::query()->create([
            'content_blocks' => [
                [
                    'type' => 'text',
                    'data' => [
                        'heading' => 'Sign up',
                        'body' => '<p>Use the form below.</p>'.$this->embedBlockHtml($embedCode),
                        'background' => 'white',
                    ],
                ],
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Use the form below.')
            ->assertSee('<div class="page-rich-embed"><iframe src="https://example.com/form"></iframe></div>', false)
            ->assertDontSee('data-type="customBlock"', false);
    }

    public function test_homepage_content_default_feature_url_still_allows_one_church_fallback(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'one_church_url' => 'https://example.com/one-church',
        ]);

        HomepageContent::query()->create([
            'content_blocks' => null,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('https://example.com/one-church');
    }

    public function test_homepage_info_strip_can_pull_values_from_site_settings(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'sunday_service_times' => '<p>8:00, 9:15, &amp; <strong>11:00</strong></p>',
            'office_hours' => '<p>Mon-Thu <strong>9-4</strong></p>',
            'address' => '<p>TwyxtCo Church 305 Keystone Hill Road <strong>Philipsburg</strong>, PA 16866</p>',
        ]);

        HomepageContent::query()->create([
            'content_blocks' => [
                [
                    'type' => 'info_strip',
                    'data' => [
                        'items' => [
                            ['label' => 'Sunday', 'source' => 'sunday_service_times', 'value' => 'Fallback Times'],
                            ['label' => 'Office', 'source' => 'office_hours', 'value' => 'Fallback Office'],
                            ['label' => 'Visit', 'source' => 'address', 'value' => 'Fallback Address'],
                            ['label' => 'Next Step', 'source' => 'custom', 'value' => '<p>Connect Card &amp; <strong>Prayer</strong></p>'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('concept-service-strip', false)
            ->assertSee('--info-strip-count: 4', false)
            ->assertSee('<strong>11:00</strong>', false)
            ->assertSee('<strong>9-4</strong>', false)
            ->assertSee('<strong>Philipsburg</strong>', false)
            ->assertSee('<strong>Prayer</strong>', false)
            ->assertDontSee('&lt;strong&gt;11:00&lt;/strong&gt;', false)
            ->assertDontSee('Fallback Times')
            ->assertDontSee('Fallback Office')
            ->assertDontSee('Fallback Address');
    }

    public function test_homepage_info_strip_is_hidden_when_no_items_have_content(): void
    {
        HomepageContent::query()->create([
            'content_blocks' => [
                [
                    'type' => 'info_strip',
                    'data' => [
                        'items' => [
                            ['label' => 'Sunday', 'source' => 'custom', 'value' => null],
                            ['label' => null, 'source' => 'custom', 'value' => 'Has a value'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('concept-service-strip', false)
            ->assertDontSee('Has a value');
    }

    public function test_homepage_info_strip_supports_spacing_options_and_up_to_five_items(): void
    {
        HomepageContent::query()->create([
            'content_blocks' => [
                [
                    'type' => 'info_strip',
                    'data' => [
                        'spacing' => 'both',
                        'items' => [
                            ['label' => 'One', 'source' => 'custom', 'value' => 'First'],
                            ['label' => 'Two', 'source' => 'custom', 'value' => 'Second'],
                            ['label' => 'Three', 'source' => 'custom', 'value' => 'Third'],
                            ['label' => 'Four', 'source' => 'custom', 'value' => 'Fourth'],
                            ['label' => 'Five', 'source' => 'custom', 'value' => 'Fifth'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('page-block--info-strip-spacing-both', false)
            ->assertSee('--info-strip-count: 5', false)
            ->assertSee('Fifth');
    }

    private function embedBlockHtml(string $embedCode, string $label = 'Signup form'): string
    {
        $config = htmlspecialchars(json_encode([
            'label' => $label,
            'embed_code' => $embedCode,
        ], JSON_THROW_ON_ERROR), ENT_QUOTES);

        return '<div data-type="customBlock" data-id="embed" data-config="'.$config.'"></div>';
    }
}

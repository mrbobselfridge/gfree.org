<?php

namespace Tests\Feature;

use App\Models\HomepageContent;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Everything a guest needs without digging.')
            ->assertSee('Visit Sunday')
            ->assertSee('Every step matters.')
            ->assertSee('One Church handles the moving parts.')
            ->assertSee('page-block--process-steps', false);
    }

    public function test_homepage_content_renders_flexible_content_blocks(): void
    {
        HomepageContent::query()->create([
            'content_blocks' => [
                [
                    'type' => 'text',
                    'data' => [
                        'eyebrow' => 'More Ways',
                        'heading' => 'Life at gFree',
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
                        'body' => 'Start with a simple form.',
                        'button_label' => 'Get Started',
                        'button_url' => '/next-step',
                        'background' => 'gold',
                    ],
                ],
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('page-block--bg-forest', false)
            ->assertSee('More Ways')
            ->assertSee('Life at gFree')
            ->assertSee('Groups, events, and serving opportunities.')
            ->assertSee('page-block--process-steps', false)
            ->assertSee('Simple process')
            ->assertSee('Pick a first step.')
            ->assertSee('page-block--bg-gold', false)
            ->assertSee('Take a next step')
            ->assertSee('Get Started')
            ->assertSee('/next-step');
    }

    public function test_homepage_content_default_feature_url_still_allows_one_church_fallback(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'one_church_url' => 'https://example.com/one-church',
        ]);

        HomepageContent::query()->create([
            'content_blocks' => null,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('https://example.com/one-church');
    }
}

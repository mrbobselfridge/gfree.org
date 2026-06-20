<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\SiteSettings\Pages\EditSiteSetting;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\SiteDesignPalette;
use Filament\Schemas\Components\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteSettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_settings_edit_page_has_sections_and_cancel_actions(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get("/admin/site-settings/{$settings->getKey()}/edit")
            ->assertOk()
            ->assertSee('Organizational Information')
            ->assertSee('Site logo')
            ->assertSee('Default page header image')
            ->assertSee('Site Design elements')
            ->assertSee('Site accent color')
            ->assertSee('Accent text color')
            ->assertSee('Soft accent color')
            ->assertSee('Background colors')
            ->assertSee('Custom CSS')
            ->assertSee('Dashboard Notes')
            ->assertSee('AI Settings')
            ->assertSee('OpenAI API key')
            ->assertSee('AI Content Prompt')
            ->assertSee('Social and Additional Links')
            ->assertSee('TikTok URL')
            ->assertSee('LinkedIn URL')
            ->assertSee('Google Business Profile URL')
            ->assertSee('Pinterest URL')
            ->assertSee('X URL')
            ->assertSee('Threads URL')
            ->assertSee('Additional links')
            ->assertDontSee('Social and Video URLs')
            ->assertDontSee('One Church URL')
            ->assertSee('Google Tracking')
            ->assertSee('Google Tag Manager container ID')
            ->assertSee('Google Analytics measurement ID')
            ->assertDontSee('Ministries Settings')
            ->assertDontSee('Sermons Settings')
            ->assertDontSee('Leaders Settings')
            ->assertDontSee('Announcements Settings')
            ->assertDontSee('Bulletins Settings')
            ->assertDontSee('Can also be managed in the Ministries area.')
            ->assertDontSee('Can also be managed in the Sermons area.')
            ->assertDontSee('Can also be managed in the Leaders area.')
            ->assertDontSee('Can also be managed in the Announcements area.')
            ->assertDontSee('Can also be managed in the Bulletins area.')
            ->assertDontSee('Sermons YouTube feed URL')
            ->assertSee('Collapse all')
            ->assertSee('Expand all')
            ->assertSee('Save');

        $this->assertGreaterThanOrEqual(2, substr_count($response->getContent(), 'Cancel'));

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->assertSchemaComponentExists('site-settings-section-controls')
            ->assertSchemaComponentExists(
                'site-settings-organizational-information',
                checkComponentUsing: fn (Section $component): bool => $component->isCollapsible()
                    && $component->isCollapsed()
                    && $component->shouldPersistCollapsed(),
            )
            ->assertSchemaComponentExists(
                'site-settings-ai-settings',
                checkComponentUsing: fn (Section $component): bool => $component->isCollapsible()
                    && $component->isCollapsed()
                    && $component->shouldPersistCollapsed(),
            )
            ->assertSchemaComponentExists(
                'site-settings-site-design-elements',
                checkComponentUsing: fn (Section $component): bool => $component->isCollapsible()
                    && $component->isCollapsed()
                    && $component->shouldPersistCollapsed(),
            )
            ->assertSchemaComponentExists(
                'site-settings-dashboard-notes',
                checkComponentUsing: fn (Section $component): bool => $component->isCollapsible()
                    && $component->isCollapsed()
                    && $component->shouldPersistCollapsed(),
            )
            ->assertSchemaComponentExists(
                'site-settings-social-and-additional-links',
                checkComponentUsing: fn (Section $component): bool => $component->isCollapsible()
                    && $component->isCollapsed()
                    && $component->shouldPersistCollapsed(),
            )
            ->assertSchemaComponentDoesNotExist('site-settings-ministries-settings');
    }

    public function test_site_settings_dashboard_notes_can_be_saved(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.dashboard_notes', '<p><a href="/admin/pages">Review pages</a> before Sunday.</p>')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'id' => $settings->getKey(),
            'dashboard_notes' => '<p><a href="/admin/pages">Review pages</a> before Sunday.</p>',
        ]);
    }

    public function test_site_settings_ai_content_prompt_can_be_saved(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.ai_content_prompt', 'Rewrite this for local church visitors.')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'id' => $settings->getKey(),
            'ai_content_prompt' => 'Rewrite this for local church visitors.',
        ]);
    }

    public function test_default_page_header_image_can_be_saved(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.default_page_header_image_path', ['site-settings/page-header-images/default-banner.jpg'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'id' => $settings->getKey(),
            'default_page_header_image_path' => 'site-settings/page-header-images/default-banner.jpg',
        ]);
    }

    public function test_site_settings_openai_fields_can_be_saved(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.openai_api_key', 'test-openai-key')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'id' => $settings->getKey(),
            'openai_api_key' => 'test-openai-key',
        ]);
    }

    public function test_site_settings_url_fields_accept_external_urls_and_local_paths(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.facebook_url', 'http://facebook.example/twyxtco')
            ->set('data.instagram_url', '/instagram')
            ->set('data.youtube_url', '/sermons')
            ->set('data.tiktok_url', 'https://tiktok.example/twyxtco')
            ->set('data.linkedin_url', 'https://linkedin.example/company/twyxtco')
            ->set('data.google_business_profile_url', 'https://business.google.com/example')
            ->set('data.pinterest_url', 'https://pinterest.example/twyxtco')
            ->set('data.x_url', 'https://x.example/twyxtco')
            ->set('data.threads_url', 'https://threads.example/@twyxtco')
            ->set('data.additional_social_links', [
                [
                    'label' => 'Podcast',
                    'url' => '/podcast',
                    'image_path' => 'site-settings/additional-links/podcast.png',
                ],
                [
                    'label' => 'Newsletter',
                    'url' => 'https://newsletter.example/twyxtco',
                    'image_path' => ['site-settings/additional-links/newsletter.png'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'id' => $settings->getKey(),
            'facebook_url' => 'http://facebook.example/twyxtco',
            'instagram_url' => '/instagram',
            'youtube_url' => '/sermons',
            'tiktok_url' => 'https://tiktok.example/twyxtco',
            'linkedin_url' => 'https://linkedin.example/company/twyxtco',
            'google_business_profile_url' => 'https://business.google.com/example',
            'pinterest_url' => 'https://pinterest.example/twyxtco',
            'x_url' => 'https://x.example/twyxtco',
            'threads_url' => 'https://threads.example/@twyxtco',
        ]);

        $this->assertSame([
            [
                'label' => 'Podcast',
                'url' => '/podcast',
                'image_path' => 'site-settings/additional-links/podcast.png',
            ],
            [
                'label' => 'Newsletter',
                'url' => 'https://newsletter.example/twyxtco',
                'image_path' => 'site-settings/additional-links/newsletter.png',
            ],
        ], $settings->refresh()->additional_social_links);
    }

    public function test_site_design_background_colors_can_be_saved(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.design_background_colors', [
                [
                    'key' => 'white',
                    'name' => 'White',
                    'hex' => '#ffffff',
                ],
                [
                    'name' => 'Midnight Blue',
                    'hex' => '102030',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $colors = $settings->refresh()->backgroundColors();

        $this->assertSame([
            ['key' => 'white', 'name' => 'White', 'hex' => '#ffffff'],
            ['key' => 'midnight-blue', 'name' => 'Midnight Blue', 'hex' => '#102030'],
        ], $colors);
        $this->assertSame('Midnight Blue', SiteDesignPalette::normalizeBackgroundColors($colors)[1]['name']);
    }

    public function test_site_design_public_colors_and_custom_css_can_be_saved(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.design_accent_color', '445566')
            ->set('data.design_accent_text_color', '#223344')
            ->set('data.design_accent_soft_color', '#ddeeff')
            ->set('data.custom_css', ".page-hero h1 {\n    text-transform: uppercase;\n}")
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'id' => $settings->getKey(),
            'design_accent_color' => '#445566',
            'design_accent_text_color' => '#223344',
            'design_accent_soft_color' => '#ddeeff',
            'custom_css' => ".page-hero h1 {\n    text-transform: uppercase;\n}",
        ]);
    }

    public function test_site_settings_editor_without_code_access_cannot_change_custom_css(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'custom_css' => '.site-header { color: red; }',
        ]);

        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [AdminAccess::SITE_SETTINGS],
                'records' => [],
            ],
        ]);

        $this->actingAs($editor)
            ->get("/admin/site-settings/{$settings->getKey()}/edit")
            ->assertOk()
            ->assertDontSee('Custom CSS');

        Livewire::actingAs($editor)
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.custom_css', '.site-header { color: blue; }')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('.site-header { color: red; }', $settings->refresh()->custom_css);
    }

    public function test_site_settings_url_fields_reject_non_url_text(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.linkedin_url', 'linkedin page')
            ->set('data.additional_social_links', [
                [
                    'label' => 'Podcast',
                    'url' => 'podcast page',
                    'image_path' => 'site-settings/additional-links/podcast.png',
                ],
            ])
            ->call('save')
            ->assertHasFormErrors([
                'linkedin_url',
                'additional_social_links.0.url',
            ]);
    }

    public function test_site_settings_google_tracking_fields_can_be_saved(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.google_tag_manager_id', 'gtm-church1')
            ->set('data.google_analytics_measurement_id', 'g-churchga1')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'id' => $settings->getKey(),
            'google_tag_manager_id' => 'GTM-CHURCH1',
            'google_analytics_measurement_id' => 'G-CHURCHGA1',
        ]);
    }

    public function test_site_settings_google_tracking_fields_reject_invalid_ids(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.google_tag_manager_id', 'G-CHURCHGA1')
            ->set('data.google_analytics_measurement_id', 'GTM-CHURCH1')
            ->call('save')
            ->assertHasFormErrors([
                'google_tag_manager_id',
                'google_analytics_measurement_id',
            ]);
    }
}

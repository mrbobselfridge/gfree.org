<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\SiteSettings\Pages\EditSiteSetting;
use App\Models\SiteSetting;
use App\Models\User;
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
            ->assertSee('AI Settings')
            ->assertSee('OpenAI API key')
            ->assertSee('AI Content Prompt')
            ->assertSee('Social and Video URLs')
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
            ->assertSchemaComponentDoesNotExist('site-settings-ministries-settings');
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
            ->set('data.livestream_url', 'https://live.example.com/twyxtco')
            ->set('data.giving_url', '/give')
            ->set('data.one_church_url', '/connect-card?source=site')
            ->set('data.facebook_url', 'http://facebook.example/twyxtco')
            ->set('data.instagram_url', '/instagram')
            ->set('data.youtube_url', '/sermons')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'id' => $settings->getKey(),
            'livestream_url' => 'https://live.example.com/twyxtco',
            'giving_url' => '/give',
            'one_church_url' => '/connect-card?source=site',
            'facebook_url' => 'http://facebook.example/twyxtco',
            'instagram_url' => '/instagram',
            'youtube_url' => '/sermons',
        ]);
    }

    public function test_site_settings_url_fields_reject_non_url_text(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.giving_url', 'give page')
            ->call('save')
            ->assertHasFormErrors(['giving_url']);
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

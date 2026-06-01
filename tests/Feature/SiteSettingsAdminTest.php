<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\SiteSettings\Pages\EditSiteSetting;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteSettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_settings_edit_page_has_sections_and_cancel_actions(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'gFree Church',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get("/admin/site-settings/{$settings->getKey()}/edit")
            ->assertOk()
            ->assertSee('Organizational Information')
            ->assertSee('AI Settings')
            ->assertSee('AI Content Prompt')
            ->assertSee('Social and Video URLs')
            ->assertSee('Announcements Settings')
            ->assertSee('Can also be managed in the Announcements area.')
            ->assertSee('Ministries Settings')
            ->assertSee('Can also be managed in the Ministries area.')
            ->assertSee('Leaders Settings')
            ->assertSee('Can also be managed in the Leaders area.')
            ->assertSee('Sermons Settings')
            ->assertSee('Can also be managed in the Sermons area.')
            ->assertSee('Bulletins Settings')
            ->assertSee('Can also be managed in the Bulletins area.')
            ->assertSee('Bulletins small label')
            ->assertSee('Bulletins title')
            ->assertSee('Bulletins subtitle')
            ->assertSee('Bulletins Image')
            ->assertSee('Save');

        $this->assertGreaterThanOrEqual(2, substr_count($response->getContent(), 'Cancel'));
    }

    public function test_site_settings_sermons_channel_url_fills_feed_url(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'gFree Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.sermons_youtube_channel_url', 'https://www.youtube.com/channel/UCSiteSettingsChannelId/videos')
            ->assertSet('data.sermons_youtube_feed_url', 'https://www.youtube.com/feeds/videos.xml?channel_id=UCSiteSettingsChannelId');
    }

    public function test_site_settings_ai_content_prompt_can_be_saved(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'gFree Church',
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
}

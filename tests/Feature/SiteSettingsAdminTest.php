<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Social and Video URLs')
            ->assertSee('Announcements Settings')
            ->assertSee('Can also be managed in the Announcements area.')
            ->assertSee('Ministries Settings')
            ->assertSee('Can also be managed in the Ministries area.')
            ->assertSee('Leaders Settings')
            ->assertSee('Can also be managed in the Leaders area.')
            ->assertSee('Sermons Settings')
            ->assertSee('Can also be managed in the Sermons area.')
            ->assertSee('Save');

        $this->assertGreaterThanOrEqual(2, substr_count($response->getContent(), 'Cancel'));
    }
}

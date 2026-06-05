<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicGoogleTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_tag_manager_renders_on_public_pages_and_takes_priority_over_direct_analytics(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'google_tag_manager_id' => 'gtm-church1',
            'google_analytics_measurement_id' => 'G-CHURCHGA1',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Google Tag Manager', false)
            ->assertSee("'GTM-CHURCH1'", false)
            ->assertSee('https://www.googletagmanager.com/ns.html?id=GTM-CHURCH1', false)
            ->assertDontSee('https://www.googletagmanager.com/gtag/js?id=G-CHURCHGA1', false)
            ->assertDontSee("gtag('config', 'G-CHURCHGA1')", false);
    }

    public function test_direct_google_analytics_renders_when_tag_manager_is_empty(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'google_analytics_measurement_id' => 'g-churchga1',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Google tag (gtag.js)', false)
            ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-CHURCHGA1', false)
            ->assertSee("gtag('config', 'G-CHURCHGA1')", false)
            ->assertDontSee('googletagmanager.com/ns.html?id=', false);
    }

    public function test_minimal_pages_without_site_chrome_still_render_tracking(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'google_tag_manager_id' => 'GTM-LANDING1',
        ]);

        Page::query()->create([
            'title' => 'Landing',
            'slug' => 'landing',
            'body' => 'Landing page body.',
            'show_site_chrome' => false,
            'show_page_header' => false,
            'is_published' => true,
        ]);

        $this->get('/landing')
            ->assertOk()
            ->assertDontSee('concept-header', false)
            ->assertSee("'GTM-LANDING1'", false)
            ->assertSee('https://www.googletagmanager.com/ns.html?id=GTM-LANDING1', false);
    }
}

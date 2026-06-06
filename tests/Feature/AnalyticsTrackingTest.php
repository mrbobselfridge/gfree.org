<?php

namespace Tests\Feature;

use App\Models\AnalyticsPageView;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;
use Tests\TestCase;

class AnalyticsTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_page_views_are_tracked_with_request_metadata(): void
    {
        Page::query()->create([
            'title' => 'About TwyxtCo',
            'slug' => 'about',
            'is_published' => true,
        ]);

        Location::fake([
            '*' => Position::make([
                'driver' => 'fake',
                'country_code' => 'US',
                'country_name' => 'United States',
                'region_code' => 'PA',
                'region_name' => 'Pennsylvania',
                'city_name' => 'Pittsburgh',
                'postal_code' => '15222',
                'timezone' => 'America/New_York',
                'latitude' => '40.4406000',
                'longitude' => '-79.9959000',
            ]),
        ]);

        $this
            ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36')
            ->from('https://www.google.com/search?q=twyxtco')
            ->get('/about')
            ->assertOk();

        $this->assertDatabaseHas(AnalyticsPageView::class, [
            'url' => 'http://127.0.0.1:8000/about',
            'path' => '/about',
            'route_name' => 'pages.show',
            'page_title' => 'About TwyxtCo',
            'referrer_url' => 'https://www.google.com/search?q=twyxtco',
            'referrer_domain' => 'google.com',
            'browser' => 'Chrome',
            'platform' => 'Windows',
            'device_type' => 'Desktop',
            'country_code' => 'US',
            'country_name' => 'United States',
            'region_code' => 'PA',
            'region_name' => 'Pennsylvania',
            'city_name' => 'Pittsburgh',
            'postal_code' => '15222',
            'timezone' => 'America/New_York',
            'location_driver' => 'fake',
        ]);

        $view = AnalyticsPageView::query()->firstOrFail();

        $this->assertNotNull($view->ip_hash);
        $this->assertNotNull($view->visitor_hash);
        $this->assertNotNull($view->session_hash);
    }

    public function test_admin_pages_are_not_tracked(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk();

        $this->assertDatabaseCount(AnalyticsPageView::class, 0);
    }
}

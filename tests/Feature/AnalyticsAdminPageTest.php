<?php

namespace Tests\Feature;

use App\Models\AnalyticsPageView;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsAdminPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_review_deeper_analytics_breakdowns(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->trackView('/resources', 'Resources', 'google.com', 'Desktop', 'Chrome', 'macOS', 'visitor-1', 'session-1', now()->subDays(2));
        $this->trackView('/resources', 'Resources', 'google.com', 'Mobile', 'Safari', 'iOS', 'visitor-2', 'session-2', now()->subDay());
        $this->trackView('/give', 'Giving', null, 'Desktop', 'Firefox', 'Windows', 'visitor-1', 'session-1', now()->subHours(2), 'Greensburg');

        $this->actingAs($admin)
            ->get('/admin/analytics')
            ->assertOk()
            ->assertSee('Daily Traffic')
            ->assertSee('Top Pages')
            ->assertSee('Referrer Traffic')
            ->assertSee('Devices')
            ->assertSee('Browsers')
            ->assertSee('Platforms')
            ->assertSee('Countries')
            ->assertSee('Regions')
            ->assertSee('Cities')
            ->assertSee('Recent Page Views')
            ->assertSee('Resources')
            ->assertSee('/resources')
            ->assertSee('google.com')
            ->assertSee('Desktop')
            ->assertSee('Chrome')
            ->assertSee('United States')
            ->assertSee('Pennsylvania')
            ->assertSee('Greensburg');
    }

    public function test_editor_can_access_analytics_when_granted_the_analytics_tool(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [AdminAccess::ANALYTICS],
                'records' => [],
            ],
        ]);

        $this->actingAs($editor)
            ->get('/admin/analytics')
            ->assertOk()
            ->assertSee('Analytics');
    }

    public function test_editor_without_analytics_permission_cannot_access_analytics(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [],
                'records' => [],
            ],
        ]);

        $this->actingAs($editor)
            ->get('/admin/analytics')
            ->assertForbidden();
    }

    private function trackView(
        string $path,
        string $title,
        ?string $referrerDomain,
        string $device,
        string $browser,
        string $platform,
        string $visitor,
        string $session,
        mixed $viewedAt,
        string $city = 'Pittsburgh',
    ): void {
        AnalyticsPageView::query()->create([
            'url' => "https://twyxtco.org{$path}",
            'path' => $path,
            'route_name' => null,
            'page_title' => $title,
            'referrer_url' => $referrerDomain ? "https://{$referrerDomain}/search" : null,
            'referrer_domain' => $referrerDomain,
            'user_agent' => "{$browser} on {$platform}",
            'browser' => $browser,
            'platform' => $platform,
            'device_type' => $device,
            'ip_hash' => "{$visitor}-ip",
            'visitor_hash' => $visitor,
            'session_hash' => $session,
            'country_code' => 'US',
            'country_name' => 'United States',
            'region_code' => 'PA',
            'region_name' => 'Pennsylvania',
            'city_name' => $city,
            'postal_code' => '15222',
            'timezone' => 'America/New_York',
            'latitude' => '40.4406000',
            'longitude' => '-79.9959000',
            'location_driver' => 'fake',
            'viewed_at' => $viewedAt,
        ]);
    }
}

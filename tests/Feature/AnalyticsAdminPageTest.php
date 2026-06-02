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

        $this->trackView('/ministries', 'Ministries', 'google.com', 'Desktop', 'Chrome', 'macOS', 'visitor-1', 'session-1', now()->subDays(2));
        $this->trackView('/ministries', 'Ministries', 'google.com', 'Mobile', 'Safari', 'iOS', 'visitor-2', 'session-2', now()->subDay());
        $this->trackView('/give', 'Giving', null, 'Desktop', 'Firefox', 'Windows', 'visitor-1', 'session-1', now()->subHours(2));

        $this->actingAs($admin)
            ->get('/admin/analytics')
            ->assertOk()
            ->assertSee('Daily Traffic')
            ->assertSee('Top Pages')
            ->assertSee('Referrer Traffic')
            ->assertSee('Devices')
            ->assertSee('Browsers')
            ->assertSee('Platforms')
            ->assertSee('Recent Page Views')
            ->assertSee('Ministries')
            ->assertSee('/ministries')
            ->assertSee('google.com')
            ->assertSee('Desktop')
            ->assertSee('Chrome');
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
    ): void {
        AnalyticsPageView::query()->create([
            'url' => "https://gfree.org{$path}",
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
            'viewed_at' => $viewedAt,
        ]);
    }
}

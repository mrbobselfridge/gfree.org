<?php

namespace Tests\Feature;

use App\Filament\Admin\CmsDashboard;
use App\Models\AnalyticsPageView;
use App\Models\NavigationLink;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminDashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_brand_uses_site_settings_church_name(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'Custom Church Name',
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Custom Church Name Dashboard');

        $this->assertTrue(CmsDashboard::shouldRegisterNavigation());
    }

    public function test_admin_dashboard_shows_cms_widgets_with_relevant_content(): void
    {
        Storage::fake('public');
        Storage::fake('backups');
        Storage::disk('public')->put('media-library/welcome.jpg', 'image-bytes');
        Storage::disk('backups')->put(config('backup_database.backup.name').'/db-2026-06-10-00-15-00.zip', 'database backup');
        Storage::disk('backups')->put(config('backup_full.backup.name').'/full-2026-06-10-01-00-00.zip', 'full backup');

        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        NavigationLink::query()->create([
            'label' => 'Home',
            'url' => '/',
            'location' => 'header',
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Draft page',
            'slug' => 'draft-page',
            'is_published' => false,
        ]);

        AnalyticsPageView::query()->create([
            'url' => 'https://twyxtco.org/',
            'path' => '/',
            'route_name' => 'home',
            'page_title' => 'Home',
            'referrer_domain' => null,
            'browser' => 'Chrome',
            'platform' => 'Windows',
            'device_type' => 'Desktop',
            'visitor_hash' => 'visitor-one',
            'session_hash' => 'session-one',
            'viewed_at' => now(),
        ]);

        AnalyticsPageView::query()->create([
            'url' => 'https://twyxtco.org/new-here',
            'path' => '/new-here',
            'route_name' => 'pages.show',
            'page_title' => 'New Here',
            'referrer_domain' => 'google.com',
            'browser' => 'Safari',
            'platform' => 'iOS',
            'device_type' => 'Mobile',
            'visitor_hash' => 'visitor-two',
            'session_hash' => 'session-two',
            'viewed_at' => now()->subDay(),
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('fi-account-widget', false)
            ->assertDontSee('fi-filament-info-widget', false)
            ->assertSee('Needs Attention')
            ->assertSee('data-twyxtco-dashboard-widget="needs-attention-widget"', false)
            ->assertSee('data-twyxtco-dashboard-widget-collapse', false)
            ->assertSee('Move')
            ->assertSee('Collapse')
            ->assertSee('Page')
            ->assertSee('Site Settings')
            ->assertSee('Media Library')
            ->assertSee('Missing Header Images')
            ->assertSee('Missing Small Labels')
            ->assertSee('Missing Intros')
            ->assertDontSee('Ministry Landing Page')
            ->assertSee('3 high priority items')
            ->assertSee('1 medium priority item')
            ->assertSee('Recent Updates')
            ->assertSee('New Media')
            ->assertSee('target="_blank"', false)
            ->assertDontSeeText('Uploaded image')
            ->assertDontSeeText('welcome.jpg')
            ->assertSee('Quick Site Health')
            ->assertSee('OpenAI API key')
            ->assertSee('Header navigation')
            ->assertSee('AI Usage')
            ->assertSee('Current-month OpenAI spend for the configured app API key.')
            ->assertSee('OpenAI usage spend unavailable')
            ->assertSee('Setup')
            ->assertSee('twyxtco-dashboard-widget-count--danger', false)
            ->assertSee('twyxtco-dashboard-widget-count--warning', false)
            ->assertSee('twyxtco-dashboard-widget-count--success', false)
            ->assertSee('1 high priority item')
            ->assertSee('4 review items')
            ->assertSee('1 good item')
            ->assertSee('/admin/site-settings/1/edit" class="twyxtco-dashboard-widget-row-status" wire:navigate', false)
            ->assertSee('Backups')
            ->assertSee('data-twyxtco-dashboard-widget="backup-status-widget"', false)
            ->assertSee('Database Backup')
            ->assertSee('Full Site Backup')
            ->assertSee('Archive Backup')
            ->assertSee('Available')
            ->assertSee('Missing')
            ->assertSee('Open backups')
            ->assertSee('Web Traffic Overview')
            ->assertSee('Views today')
            ->assertSee('Top Pages')
            ->assertSee('Referrer Traffic')
            ->assertSee('Direct / unknown')
            ->assertSee('google.com')
            ->assertSee('Browsers / Devices')
            ->assertSee('Chrome')
            ->assertSee('Mobile')
            ->assertSee('twyxtco-dashboard-widget-count')
            ->assertDontSeeText('Page Views')
            ->assertDontSee('class="twyxtco-dashboard-widget-type">Analytics', false)
            ->assertDontSee('data-twyxtco-dashboard-widget="dashboard-notes-widget"', false)
            ->assertSee('twyxtco.admin.dashboard.widgets.v1');

        $content = $response->getContent();

        foreach ([
            'analytics-overview-widget',
            'top-pages-widget',
            'top-referrers-widget',
            'browsers-devices-widget',
        ] as $widgetKey) {
            $start = strpos($content, 'data-twyxtco-dashboard-widget="'.$widgetKey.'"');

            $this->assertIsInt($start);
            $nextWidgetStart = strpos($content, 'data-twyxtco-dashboard-widget="', $start + 1);
            $widgetMarkup = $nextWidgetStart === false
                ? substr($content, $start)
                : substr($content, $start, $nextWidgetStart - $start);

            $this->assertStringNotContainsString('twyxtco-dashboard-widget-count', $widgetMarkup);
        }
    }

    public function test_admin_dashboard_ai_usage_widget_shows_current_month_app_key_spend(): void
    {
        Cache::flush();

        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'openai_api_key_id' => 'key_app_123',
            'openai_admin_api_key' => 'test-admin-key',
        ]);

        Http::fake([
            'https://api.openai.com/v1/organization/costs*' => Http::response([
                'data' => [
                    [
                        'results' => [
                            [
                                'api_key_id' => 'key_app_123',
                                'amount' => [
                                    'value' => 4.25,
                                    'currency' => 'usd',
                                ],
                            ],
                            [
                                'api_key_id' => 'key_other_456',
                                'amount' => [
                                    'value' => 20.00,
                                    'currency' => 'usd',
                                ],
                            ],
                        ],
                    ],
                    [
                        'results' => [
                            [
                                'api_key_id' => 'key_app_123',
                                'amount' => [
                                    'value' => 1.75,
                                    'currency' => 'usd',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('data-twyxtco-dashboard-widget="ai-usage-widget"', false)
            ->assertSee('AI Usage')
            ->assertSee('Current month usage spend')
            ->assertSee('$6.00')
            ->assertDontSee('$20.00')
            ->assertSee('Billing top-ups and prepaid credit purchases are separate from usage spend.');

        Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api.openai.com/v1/organization/costs')
            && $request->hasHeader('Authorization', 'Bearer test-admin-key')
            && str_contains($request->url(), 'group_by%5B0%5D=api_key_id'));
    }

    public function test_admin_dashboard_shows_rich_dashboard_notes_widget_when_configured(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'dashboard_notes' => '<h2>Sunday reminders</h2><p><a href="/admin/pages">Review pages</a> before publishing.</p><ul><li>Check forms</li></ul>',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('data-twyxtco-dashboard-widget="dashboard-notes-widget"', false)
            ->assertSee('Dashboard notes')
            ->assertSee('Links and notes from Site Settings.')
            ->assertSee('Sunday reminders')
            ->assertSee('<a href="/admin/pages">Review pages</a>', false)
            ->assertSee('Check forms')
            ->assertSee('Edit notes')
            ->assertSee('Move Dashboard notes');

        $content = $response->getContent();
        $start = strpos($content, 'data-twyxtco-dashboard-widget="dashboard-notes-widget"');

        $this->assertIsInt($start);
        $nextWidgetStart = strpos($content, 'data-twyxtco-dashboard-widget="', $start + 1);
        $widgetMarkup = $nextWidgetStart === false
            ? substr($content, $start)
            : substr($content, $start, $nextWidgetStart - $start);

        $this->assertStringContainsString('data-twyxtco-dashboard-widget-drag-handle', $widgetMarkup);
        $this->assertStringNotContainsString('data-twyxtco-dashboard-widget-collapse', $widgetMarkup);
    }

    public function test_dashboard_widgets_respect_editor_admin_permissions(): void
    {
        Page::query()->create([
            'title' => 'Allowed page',
            'slug' => 'allowed-page',
            'is_published' => false,
        ]);

        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [AdminAccess::PAGES],
                'records' => [],
            ],
        ]);

        $this->actingAs($editor)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Allowed page')
            ->assertDontSee('Hidden ministry');
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Admin\CmsDashboard;
use App\Models\AnalyticsPageView;
use App\Models\Announcement;
use App\Models\Bulletin;
use App\Models\Ministry;
use App\Models\NavigationLink;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->assertFalse(CmsDashboard::shouldRegisterNavigation());
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

        Announcement::query()->create([
            'title' => 'Draft announcement',
            'slug' => 'draft-announcement',
            'is_published' => false,
            'publish_at' => now()->addDays(3),
            'expires_at' => now()->addDays(12),
        ]);

        Bulletin::query()->create([
            'title' => 'Sunday bulletin',
            'pdf_path' => 'bulletins/pdfs/sunday.pdf',
            'is_published' => false,
        ]);

        Ministry::query()->create([
            'name' => 'Draft ministry',
            'slug' => 'draft-ministry',
            'is_published' => false,
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
            'url' => 'https://twyxtco.org/ministry',
            'path' => '/ministry',
            'route_name' => 'ministries.index',
            'page_title' => 'Ministries',
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
            ->assertSee('Announcement')
            ->assertSee('Bulletin')
            ->assertSee('Ministry')
            ->assertSee('Page')
            ->assertSee('Site Settings')
            ->assertSee('Media Library')
            ->assertSee('Draft announcement')
            ->assertSee('Sunday bulletin')
            ->assertSee('Draft ministry')
            ->assertSee('Missing Header Images')
            ->assertSee('Missing Small Labels')
            ->assertSee('Missing Intros')
            ->assertSee('Announcements Landing Page')
            ->assertSee('Bulletins Landing Page')
            ->assertSee('Ministry Landing Page')
            ->assertSee('4 high priority items')
            ->assertSee('4 medium priority items')
            ->assertSee('Recent Updates')
            ->assertSee('Announcements')
            ->assertSee('Publishes')
            ->assertSee('Expires')
            ->assertSee('New Media')
            ->assertSee('target="_blank"', false)
            ->assertDontSeeText('Uploaded image')
            ->assertDontSeeText('welcome.jpg')
            ->assertSee('Quick Site Health')
            ->assertSee('OpenAI API key')
            ->assertSee('Header navigation')
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

    public function test_dashboard_widgets_respect_editor_admin_permissions(): void
    {
        Announcement::query()->create([
            'title' => 'Allowed announcement',
            'slug' => 'allowed-announcement',
            'is_published' => false,
        ]);

        Ministry::query()->create([
            'name' => 'Hidden ministry',
            'slug' => 'hidden-ministry',
            'is_published' => false,
        ]);

        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [AdminAccess::ANNOUNCEMENTS],
                'records' => [],
            ],
        ]);

        $this->actingAs($editor)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Allowed announcement')
            ->assertDontSee('Hidden ministry');
    }
}

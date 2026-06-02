<?php

namespace Tests\Feature;

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

    public function test_admin_dashboard_shows_cms_widgets_with_relevant_content(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media-library/welcome.jpg', 'image-bytes');

        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'sermons_youtube_channel_url' => 'https://www.youtube.com/@gfreesermons9521',
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
            'url' => 'https://gfree.org/',
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
            'url' => 'https://gfree.org/ministry',
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

        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('fi-account-widget', false)
            ->assertDontSee('fi-filament-info-widget', false)
            ->assertSee('Needs Attention')
            ->assertSee('data-gfree-dashboard-widget="needs-attention-widget"', false)
            ->assertSee('data-gfree-dashboard-widget-collapse', false)
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
            ->assertSee('gfree-dashboard-widget-count--danger', false)
            ->assertSee('gfree-dashboard-widget-count--warning', false)
            ->assertSee('gfree-dashboard-widget-count--success', false)
            ->assertSee('1 high priority item')
            ->assertSee('4 review items')
            ->assertSee('2 good items')
            ->assertSee('/admin/site-settings/1/edit" class="gfree-dashboard-widget-row-status" wire:navigate', false)
            ->assertSee('Web Traffic Overview')
            ->assertSee('Views today')
            ->assertSee('Top Pages')
            ->assertSee('Referrer Traffic')
            ->assertSee('Direct / unknown')
            ->assertSee('google.com')
            ->assertSee('Browsers / Devices')
            ->assertSee('Chrome')
            ->assertSee('Mobile')
            ->assertSee('gfree-dashboard-widget-count')
            ->assertDontSeeText('Page Views')
            ->assertDontSee('class="gfree-dashboard-widget-type">Analytics', false)
            ->assertSee('gfree.admin.dashboard.widgets.v1');
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

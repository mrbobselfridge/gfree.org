<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Bulletin;
use App\Models\Ministry;
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

        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Needs Attention')
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
            ->assertSee('Upcoming / Expiring Announcements')
            ->assertSee('Publishes')
            ->assertSee('Expires')
            ->assertSee('New Media')
            ->assertSee('welcome.jpg')
            ->assertSee('Quick Site Health')
            ->assertSee('OpenAI API key')
            ->assertSee('Header navigation')
            ->assertSee('/admin/site-settings/1/edit" class="shrink-0" wire:navigate', false);
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

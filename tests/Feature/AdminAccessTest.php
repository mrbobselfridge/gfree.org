<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Models\NavigationLink;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminAccess;
use Filament\Schemas\Components\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_user_management_in_site_tools(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]))
            ->get('/admin/users/create')
            ->assertOk()
            ->assertSee('User Details')
            ->assertSee('twyxtco-user-permission-list', false)
            ->assertSee('Content')
            ->assertSee('Site Tools')
            ->assertSee('Homepage Content')
            ->assertSee('Homepage Banners')
            ->assertSee('Pages')
            ->assertDontSee('Ministries')
            ->assertDontSee('Sermons')
            ->assertDontSee('Announcements')
            ->assertDontSee('Bulletins')
            ->assertSee('Site Settings')
            ->assertSee('Analytics')
            ->assertSee('Backups')
            ->assertSee('Media Library')
            ->assertSee('Workflow Notifications')
            ->assertSee('Navigation Links')
            ->assertSee('Users')
            ->assertSee('Individual Page Entries')
            ->assertSee('Collapse all')
            ->assertSee('Expand all');

        $this->assertSame([], AdminAccess::toolOptionsForGroup('Homepage'));
        $this->assertSame(
            [AdminAccess::HOMEPAGE_CONTENT, AdminAccess::HOMEPAGE_BANNERS],
            array_slice(array_keys(AdminAccess::toolOptionsForGroup('Content')), 0, 2),
        );
        $contentTools = array_keys(AdminAccess::toolOptionsForGroup('Content'));

        $this->assertContains(AdminAccess::MEDIA_LIBRARY, $contentTools);
        $this->assertContains(AdminAccess::FILE_LIBRARY, $contentTools);
        $this->assertContains(AdminAccess::NAVIGATION_LINKS, $contentTools);
        $this->assertArrayNotHasKey(AdminAccess::MEDIA_LIBRARY, AdminAccess::toolOptionsForGroup('Site Tools'));
        $this->assertArrayNotHasKey(AdminAccess::FILE_LIBRARY, AdminAccess::toolOptionsForGroup('Site Tools'));
        $this->assertArrayNotHasKey(AdminAccess::NAVIGATION_LINKS, AdminAccess::toolOptionsForGroup('Site Tools'));
        $this->assertArrayHasKey(AdminAccess::WORKFLOW_NOTIFICATIONS, AdminAccess::toolOptionsForGroup('Site Tools'));
        $this->assertArrayHasKey(AdminAccess::BACKUPS, AdminAccess::toolOptionsForGroup('Site Tools'));
        $this->assertArrayNotHasKey(AdminAccess::WORKFLOW_NOTIFICATIONS, AdminAccess::additionalToolOptions());

        Livewire::actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]))
            ->test(CreateUser::class)
            ->assertSchemaComponentExists('users-section-controls')
            ->assertSchemaComponentExists(
                'users-user-details',
                checkComponentUsing: fn (Section $component): bool => ! $component->isCollapsible()
                    && ! $component->isCollapsed()
            )
            ->assertSchemaComponentExists(
                'users-content-tools',
                checkComponentUsing: fn (Section $component): bool => $this->isCollapsedUserPermissionSection($component),
            )
            ->assertSchemaComponentExists(
                'users-sitewide-tools',
                checkComponentUsing: fn (Section $component): bool => $this->isCollapsedUserPermissionSection($component),
            )
            ->assertSchemaComponentDoesNotExist('users-individual-ministry-entries')
            ->assertSchemaComponentExists(
                'users-individual-page-entries',
                checkComponentUsing: fn (Section $component): bool => $this->isCollapsedUserPermissionSection($component),
            );
    }

    private function isCollapsedUserPermissionSection(Section $component): bool
    {
        return $component->isCollapsible()
            && $component->isCollapsed()
            && $component->shouldPersistCollapsed();
    }

    public function test_editor_only_accesses_selected_admin_areas(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        NavigationLink::query()->create([
            'label' => 'About',
            'url' => '/about',
        ]);

        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [AdminAccess::SITE_SETTINGS],
                'records' => [],
            ],
        ]);

        $this->actingAs($editor)
            ->get("/admin/site-settings/{$settings->getKey()}/edit")
            ->assertOk();

        $this->actingAs($editor)
            ->get('/admin/navigation-links')
            ->assertForbidden();

        $this->actingAs($editor)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_editor_with_user_management_access_can_open_users_tool(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [AdminAccess::USERS],
                'records' => [],
            ],
        ]);

        $this->actingAs($editor)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Users');
    }

    public function test_editor_standalone_page_access_is_restricted_to_selected_pages(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [AdminAccess::MEDIA_LIBRARY],
                'records' => [],
            ],
        ]);

        $this->actingAs($editor)
            ->get('/admin/sermons')
            ->assertNotFound();

        $this->actingAs($editor)
            ->get('/admin/media-library')
            ->assertOk();

        $this->actingAs($editor)
            ->get('/admin/homepage-content')
            ->assertForbidden();
    }

    public function test_user_management_saves_editor_permissions(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->set('data.name', 'Content Editor')
            ->set('data.email', 'editor@example.com')
            ->set('data.password', 'password')
            ->set('data.role', User::ROLE_EDITOR)
            ->set('data.admin_permissions.tool_groups.content', [AdminAccess::HOMEPAGE_CONTENT, AdminAccess::MEDIA_LIBRARY])
            ->set('data.admin_permissions.tool_groups.sitewide', [AdminAccess::SITE_SETTINGS])
            ->call('create')
            ->assertHasNoErrors();

        $editor = User::query()->where('email', 'editor@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_EDITOR, $editor->role);
        $this->assertSame([AdminAccess::HOMEPAGE_CONTENT, AdminAccess::MEDIA_LIBRARY, AdminAccess::SITE_SETTINGS], $editor->admin_permissions['tools']);
        $this->assertArrayNotHasKey('ministries', $editor->admin_permissions['records']);

        $this->actingAs($editor)
            ->get('/admin/media-library')
            ->assertOk();
    }

    public function test_ministries_admin_area_is_removed(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]))
            ->get('/admin/ministries')
            ->assertNotFound();
    }

    public function test_legacy_ministry_permissions_are_ignored(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => ['ministries'],
                'records' => [
                    'ministries' => ['1'],
                ],
            ],
        ]);

        $this->actingAs($editor)
            ->get('/admin/ministries')
            ->assertNotFound();
    }
}

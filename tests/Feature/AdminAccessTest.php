<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Models\Ministry;
use App\Models\NavigationLink;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_user_management_in_sitewide_tools(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]))
            ->get('/admin/users/create')
            ->assertOk()
            ->assertSee('User Details')
            ->assertSee('Approved Admin Areas')
            ->assertSee('Homepage Content')
            ->assertSee('Homepage Banners')
            ->assertSee('Announcements')
            ->assertSee('Ministries')
            ->assertSee('Pages')
            ->assertSee('Leaders')
            ->assertSee('Sermons')
            ->assertSee('Site Settings')
            ->assertSee('Navigation Links')
            ->assertSee('Users')
            ->assertSee('Individual Ministry Entries')
            ->assertSee('Individual Page Entries')
            ->assertSee('Individual Leader Entries');
    }

    public function test_editor_only_accesses_selected_admin_areas(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'gFree Church',
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
                'tools' => [AdminAccess::SERMONS],
                'records' => [],
            ],
        ]);

        $this->actingAs($editor)
            ->get('/admin/sermons')
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

        $ministry = Ministry::query()->create([
            'name' => 'Worship Ministry',
            'slug' => 'worship-ministry',
            'is_published' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->set('data.name', 'Content Editor')
            ->set('data.email', 'editor@example.com')
            ->set('data.password', 'password')
            ->set('data.role', User::ROLE_EDITOR)
            ->set('data.admin_permissions.tool_groups.sitewide', [AdminAccess::SITE_SETTINGS])
            ->set('data.admin_permissions.records.ministries', [(string) $ministry->getKey()])
            ->call('create')
            ->assertHasNoErrors();

        $editor = User::query()->where('email', 'editor@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_EDITOR, $editor->role);
        $this->assertSame([AdminAccess::SITE_SETTINGS], $editor->admin_permissions['tools']);
        $this->assertEquals([(string) $ministry->getKey()], $editor->admin_permissions['records']['ministries']);
    }

    public function test_editor_with_individual_ministry_access_only_sees_assigned_ministries(): void
    {
        $allowed = Ministry::query()->create([
            'name' => 'Allowed Ministry',
            'slug' => 'allowed-ministry',
            'is_published' => true,
        ]);

        $blocked = Ministry::query()->create([
            'name' => 'Blocked Ministry',
            'slug' => 'blocked-ministry',
            'is_published' => true,
        ]);

        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [],
                'records' => [
                    AdminAccess::MINISTRIES => [(string) $allowed->getKey()],
                ],
            ],
        ]);

        $this->actingAs($editor)
            ->get('/admin/ministries')
            ->assertOk()
            ->assertSee('Allowed Ministry')
            ->assertDontSee('Blocked Ministry');

        $this->actingAs($editor)
            ->get("/admin/ministries/{$allowed->getKey()}/edit")
            ->assertOk();

        $this->actingAs($editor)
            ->get("/admin/ministries/{$blocked->getKey()}/edit")
            ->assertNotFound();
    }
}

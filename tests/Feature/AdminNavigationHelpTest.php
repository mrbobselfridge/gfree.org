<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavigationHelpTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_navigation_outputs_help_icons_and_descriptions(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('twyxtco-sidebar-help', false)
            ->assertSee('twyxtco-sidebar-indent-30', false)
            ->assertSee('Your starting point for admin tools and quick account access.', false)
            ->assertDontSee('Manage ministry listing cards and individual ministry detail pages.', false)
            ->assertSee('Create and edit website pages, nested page paths, and simple redirect URLs.', false)
            ->assertSee('Run and review database, full-site, and archive backups for recovery planning.', false)
            ->assertSee('Send automatic or manual email updates when selected content areas are created, changed, or deleted.', false)
            ->assertSee('Manage admin and editor accounts and their allowed admin areas.', false)
            ->assertSee('/manual#workflow-notifications', false);
    }
}

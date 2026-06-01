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
            ->assertSee('gfree-sidebar-help', false)
            ->assertSee('Your starting point for admin tools and quick account access.', false)
            ->assertSee('Manage ministry listing cards and individual ministry detail pages.', false)
            ->assertSee('Manage admin and editor accounts and their allowed admin areas.', false);
    }
}

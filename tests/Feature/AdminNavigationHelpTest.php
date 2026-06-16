<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\MediaLibrary;
use App\Filament\Admin\Resources\FileDocuments\FileDocumentResource;
use App\Filament\Admin\Resources\HomepageBanners\HomepageBannerResource;
use App\Filament\Admin\Resources\NavigationLinks\NavigationLinkResource;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Filament\Admin\Resources\SiteSettings\SiteSettingResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavigationHelpTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_navigation_outputs_help_icons_and_descriptions(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get('/admin');

        $response
            ->assertOk()
            ->assertSee('twyxtco-sidebar-help', false)
            ->assertSee('twyxtco-sidebar-indent-35', false)
            ->assertDontSee('twyxtco-sidebar-tight-above', false)
            ->assertSee('Your starting point for admin tools and quick account access.', false)
            ->assertDontSee('Manage ministry listing cards and individual ministry detail pages.', false)
            ->assertSee('Create and edit website pages, nested page paths, and simple redirect URLs.', false)
            ->assertSee('Run and review database, full-site, and archive backups for recovery planning.', false)
            ->assertSee('Send automatic or manual email updates when selected content areas are created, changed, or deleted.', false)
            ->assertSee('Manage admin and editor accounts and their allowed admin areas.', false)
            ->assertSee('/manual#workflow-notifications', false);

        $this->assertGreaterThanOrEqual(3, substr_count($response->getContent(), 'twyxtco-sidebar-indent-35'));
        $this->assertSame(1, HomepageBannerResource::getNavigationSort());
        $this->assertGreaterThan(PageResource::getNavigationSort(), NavigationLinkResource::getNavigationSort());
        $this->assertGreaterThan(MediaLibrary::getNavigationSort(), FileDocumentResource::getNavigationSort());
        $this->assertLessThan(0, SiteSettingResource::getNavigationSort());
    }
}

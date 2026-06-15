<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Admin\Resources\Bulletins\Pages\ListBulletins;
use App\Filament\Admin\Resources\Ministries\Pages\ListMinistries;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MinistryListingAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_ministry_listing_settings_appear_above_ministries_table(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'ministry_image_path' => 'site-settings/ministry/ministries.jpg',
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/admin/ministries')
            ->assertOk()
            ->assertSee('Ministries Landing Page Content')
            ->assertSee('twyxtco-ministry-table-toolbar-heading', false)
            ->assertSee('Ministries')
            ->assertSee('Ministry small label')
            ->assertSee('Ministry title')
            ->assertSee('Ministry subtitle')
            ->assertSee('Ministry image')
            ->assertSee('Expand')
            ->assertSee('Collapse')
            ->assertSee('Save Landing Page Settings');
    }

    public function test_ministry_listing_settings_can_be_saved_from_list_page(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ListMinistries::class)
            ->set('listingSettingsData.ministry_small_label', 'Ephesians 4:11-14')
            ->set('listingSettingsData.ministry_title', 'Ministries')
            ->set('listingSettingsData.ministry_subtitle', '<p>Equipping the saints for ministry.</p>')
            ->call('saveListingSettings')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'church_name' => 'TwyxtCo Church',
            'ministry_small_label' => 'Ephesians 4:11-14',
            'ministry_title' => 'Ministries',
            'ministry_subtitle' => '<p>Equipping the saints for ministry.</p>',
        ]);
    }

    public function test_announcements_listing_settings_appear_above_announcements_table(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/announcements')
            ->assertOk()
            ->assertSee('Announcements Landing Page Content')
            ->assertSee('twyxtco-announcements-table-toolbar-heading', false)
            ->assertSee('Individual Announcements')
            ->assertSee('Announcements small label')
            ->assertSee('Announcements title')
            ->assertSee('Announcements subtitle')
            ->assertSee('Announcements image')
            ->assertSee('Save Landing Page Settings');
    }

    public function test_announcements_listing_settings_can_be_saved_from_list_page(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ListAnnouncements::class)
            ->set('listingSettingsData.announcements_small_label', 'Latest')
            ->set('listingSettingsData.announcements_title', 'Church updates')
            ->set('listingSettingsData.announcements_subtitle', '<p>Important things to know.</p>')
            ->call('saveListingSettings')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'church_name' => 'TwyxtCo Church',
            'announcements_small_label' => 'Latest',
            'announcements_title' => 'Church updates',
            'announcements_subtitle' => '<p>Important things to know.</p>',
        ]);
    }

    public function test_bulletins_listing_settings_appear_above_bulletins_table(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/bulletins')
            ->assertOk()
            ->assertSee('Bulletins Landing Page Content')
            ->assertSee('Bulletins small label')
            ->assertSee('Bulletins title')
            ->assertSee('Bulletins subtitle')
            ->assertSee('Bulletins image')
            ->assertSee('Expand')
            ->assertSee('Collapse')
            ->assertSee('Save Landing Page Settings');
    }

    public function test_bulletins_listing_settings_can_be_saved_from_list_page(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ListBulletins::class)
            ->set('listingSettingsData.bulletins_small_label', 'Weekly')
            ->set('listingSettingsData.bulletins_title', 'Bulletins')
            ->set('listingSettingsData.bulletins_subtitle', '<p>Follow along with this week.</p>')
            ->call('saveListingSettings')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'church_name' => 'TwyxtCo Church',
            'bulletins_small_label' => 'Weekly',
            'bulletins_title' => 'Bulletins',
            'bulletins_subtitle' => '<p>Follow along with this week.</p>',
        ]);
    }

    public function test_sermons_admin_page_is_removed(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/sermons')
            ->assertNotFound();
    }
}

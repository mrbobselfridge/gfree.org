<?php

namespace Tests\Feature;

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

    public function test_sermons_admin_page_is_removed(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/sermons')
            ->assertNotFound();
    }
}

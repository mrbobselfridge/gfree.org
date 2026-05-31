<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Sermons as SermonsPage;
use App\Filament\Admin\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Admin\Resources\Bulletins\Pages\ListBulletins;
use App\Filament\Admin\Resources\Ministries\Pages\ListMinistries;
use App\Filament\Admin\Resources\StaffMembers\Pages\ListStaffMembers;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class MinistryListingAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_ministry_listing_settings_appear_above_ministries_table(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'ministry_image_path' => 'site-settings/ministry/ministries.jpg',
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/admin/ministries')
            ->assertOk()
            ->assertSee('Ministries Landing Page Content')
            ->assertSee('gfree-ministry-table-toolbar-heading', false)
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
            'church_name' => 'gFree Church',
            'ministry_small_label' => 'Ephesians 4:11-14',
            'ministry_title' => 'Ministries',
            'ministry_subtitle' => '<p>Equipping the saints for ministry.</p>',
        ]);
    }

    public function test_leadership_listing_settings_appear_above_leadership_table(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/staff-members')
            ->assertOk()
            ->assertSee('Leadership Landing Page Content')
            ->assertSee('gfree-leadership-table-toolbar-heading', false)
            ->assertSee('Individual Leaders')
            ->assertSee('Leadership small label')
            ->assertSee('Leadership title')
            ->assertSee('Leadership subtitle')
            ->assertSee('Leadership image')
            ->assertSee('Save Landing Page Settings');
    }

    public function test_leadership_listing_settings_can_be_saved_from_list_page(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ListStaffMembers::class)
            ->set('listingSettingsData.leadership_small_label', 'Our team')
            ->set('listingSettingsData.leadership_title', 'Leaders who serve')
            ->set('listingSettingsData.leadership_subtitle', '<p>Meet the team.</p>')
            ->call('saveListingSettings')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'church_name' => 'gFree Church',
            'leadership_small_label' => 'Our team',
            'leadership_title' => 'Leaders who serve',
            'leadership_subtitle' => '<p>Meet the team.</p>',
        ]);
    }

    public function test_announcements_listing_settings_appear_above_announcements_table(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/announcements')
            ->assertOk()
            ->assertSee('Announcements Landing Page Content')
            ->assertSee('gfree-announcements-table-toolbar-heading', false)
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
            'church_name' => 'gFree Church',
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
            'church_name' => 'gFree Church',
            'bulletins_small_label' => 'Weekly',
            'bulletins_title' => 'Bulletins',
            'bulletins_subtitle' => '<p>Follow along with this week.</p>',
        ]);
    }

    public function test_sermons_landing_page_settings_have_a_dedicated_admin_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/sermons')
            ->assertOk()
            ->assertSee('Sermons Landing Page Content')
            ->assertSee('Sermons small label')
            ->assertSee('Sermons title')
            ->assertSee('Sermons subtitle')
            ->assertSee('Sermons text')
            ->assertSee('View on YouTube text')
            ->assertSee('Sermons YouTube feed URL')
            ->assertSee('Sermons YouTube channel URL')
            ->assertSee('Sermons image')
            ->assertSee('Save Landing Page Settings');
    }

    public function test_sermons_landing_page_settings_can_be_saved(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(SermonsPage::class)
            ->set('listingSettingsData.sermons_small_label', 'Messages')
            ->set('listingSettingsData.sermons_title', 'Latest sermons')
            ->set('listingSettingsData.sermons_subtitle', '<p>Messages for real life.</p>')
            ->set('listingSettingsData.sermons_text', '<p>Catch up on Sunday teaching.</p>')
            ->set('listingSettingsData.sermons_youtube_link_label', 'Open the sermon channel')
            ->set('listingSettingsData.sermons_youtube_feed_url', 'https://example.com/sermons.xml')
            ->set('listingSettingsData.sermons_youtube_channel_url', 'https://www.youtube.com/@customsermons/videos')
            ->call('saveListingSettings')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'church_name' => 'gFree Church',
            'sermons_small_label' => 'Messages',
            'sermons_title' => 'Latest sermons',
            'sermons_subtitle' => '<p>Messages for real life.</p>',
            'sermons_text' => '<p>Catch up on Sunday teaching.</p>',
            'sermons_youtube_link_label' => 'Open the sermon channel',
            'sermons_youtube_feed_url' => 'https://example.com/sermons.xml',
            'sermons_youtube_channel_url' => 'https://www.youtube.com/@customsermons/videos',
        ]);
    }

    public function test_sermons_channel_url_with_channel_id_fills_feed_url(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(SermonsPage::class)
            ->set('listingSettingsData.sermons_youtube_channel_url', 'https://www.youtube.com/channel/UCDirectChannelId/videos')
            ->assertSet('listingSettingsData.sermons_youtube_feed_url', 'https://www.youtube.com/feeds/videos.xml?channel_id=UCDirectChannelId');
    }

    public function test_sermons_channel_handle_fills_feed_url_when_youtube_page_has_channel_id(): void
    {
        Http::fake([
            'https://www.youtube.com/@customsermons/videos' => Http::response(
                '<html><head><meta itemprop="channelId" content="UCResolvedChannelId"></head></html>',
                200,
            ),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(SermonsPage::class)
            ->set('listingSettingsData.sermons_youtube_channel_url', 'https://www.youtube.com/@customsermons/videos')
            ->assertSet('listingSettingsData.sermons_youtube_feed_url', 'https://www.youtube.com/feeds/videos.xml?channel_id=UCResolvedChannelId');
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Admin\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Admin\Resources\Ministries\Pages\EditMinistry;
use App\Filament\Admin\Resources\Pages\Pages\CreatePage;
use App\Filament\Admin\Resources\Pages\Pages\EditPage;
use App\Filament\Admin\Resources\StaffMembers\Pages\EditStaffMember;
use App\Models\Announcement;
use App\Models\Ministry;
use App\Models\Page;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SlugAutoUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_slug_auto_updates_on_create_but_not_edit(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreatePage::class)
            ->set('data.title', 'New Visitor Page')
            ->assertSet('data.slug', 'new-visitor-page');

        $page = Page::query()->create([
            'title' => 'Original Page',
            'slug' => 'stable-page-url',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditPage::class, ['record' => $page->getKey()])
            ->set('data.title', 'Changed Page Title')
            ->assertSet('data.slug', 'stable-page-url');
    }

    public function test_announcement_slug_does_not_auto_update_on_edit(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateAnnouncement::class)
            ->set('data.title', 'Important Update')
            ->assertSet('data.slug', 'important-update');

        $announcement = Announcement::query()->create([
            'title' => 'Original Announcement',
            'slug' => 'stable-announcement-url',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditAnnouncement::class, ['record' => $announcement->getKey()])
            ->set('data.title', 'Changed Announcement Title')
            ->assertSet('data.slug', 'stable-announcement-url');
    }

    public function test_ministry_slug_does_not_auto_update_on_edit(): void
    {
        $ministry = Ministry::query()->create([
            'name' => 'Original Ministry',
            'slug' => 'stable-ministry-url',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditMinistry::class, ['record' => $ministry->getKey()])
            ->set('data.name', 'Changed Ministry Name')
            ->assertSet('data.slug', 'stable-ministry-url');
    }

    public function test_leadership_slug_does_not_auto_update_on_edit(): void
    {
        $leader = StaffMember::query()->create([
            'name' => 'Original Leader',
            'slug' => 'stable-leader-url',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditStaffMember::class, ['record' => $leader->getKey()])
            ->set('data.name', 'Changed Leader Name')
            ->assertSet('data.slug', 'stable-leader-url');
    }
}

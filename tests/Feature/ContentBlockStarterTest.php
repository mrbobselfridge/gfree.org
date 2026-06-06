<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Admin\Resources\Ministries\Pages\CreateMinistry;
use App\Filament\Admin\Resources\Pages\Pages\CreatePage;
use App\Filament\Admin\Resources\Pages\Pages\EditPage;
use App\Filament\Admin\Resources\StaffMembers\Pages\CreateStaffMember;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContentBlockStarterTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_pages_start_with_an_open_text_content_block(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreatePage::class)
            ->assertSet('data.content_blocks', fn (array $blocks): bool => $this->hasOneStarterTextBlock($blocks));
    }

    public function test_create_announcements_start_with_a_text_content_block(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateAnnouncement::class)
            ->assertSet('data.content_blocks', fn (array $blocks): bool => $this->hasOneStarterTextBlock($blocks))
            ->assertSee('Small label')
            ->assertSee('Content width');
    }

    public function test_create_ministries_start_with_a_text_content_block(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateMinistry::class)
            ->assertSet('data.content_blocks', fn (array $blocks): bool => $this->hasOneStarterTextBlock($blocks));
    }

    public function test_create_leaders_start_with_a_text_content_block(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateStaffMember::class)
            ->assertSet('data.content_blocks', fn (array $blocks): bool => $this->hasOneStarterTextBlock($blocks));
    }

    public function test_edit_pages_without_content_blocks_do_not_get_new_starter_blocks(): void
    {
        $page = Page::query()->create([
            'title' => 'Blank Page',
            'slug' => 'blank-page',
            'content_blocks' => null,
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditPage::class, ['record' => $page->getKey()])
            ->assertSet('data.content_blocks', []);
    }

    private function hasOneStarterTextBlock(array $blocks): bool
    {
        $block = collect($blocks)->first();

        return count($blocks) === 1
            && ($block['type'] ?? null) === 'text'
            && ($block['data']['background'] ?? null) === 'white'
            && ($block['data']['content_width'] ?? null) === 'medium';
    }
}

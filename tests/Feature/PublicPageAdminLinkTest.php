<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\HomepageContent;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Filament\Admin\Resources\Pages\Pages\CreatePage;
use App\Filament\Admin\Resources\Pages\Pages\EditPage;
use App\Filament\Admin\Resources\Pages\Pages\ListPages;
use App\Models\HomepageBanner;
use App\Models\NavigationLink;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicPageAdminLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_url_models_resolve_their_live_site_urls(): void
    {
        $this->assertSame(url('/visit'), (new Page(['slug' => 'visit']))->publicUrl());
        $this->assertSame(url('/learn/baptism/basics'), (new Page(['slug' => 'learn/baptism/basics']))->publicUrl());
        $this->assertSame(route('home'), (new HomepageBanner)->publicUrl());
        $this->assertSame(url('/new-here'), (new NavigationLink(['url' => '/new-here']))->publicUrl());
        $this->assertSame('https://example.com/live', (new NavigationLink(['url' => 'https://example.com/live']))->publicUrl());
        $this->assertNull((new NavigationLink(['url' => '#']))->publicUrl());
    }

    public function test_table_rows_show_view_action_for_records_with_public_urls(): void
    {
        $page = Page::query()->create([
            'title' => 'Visit',
            'slug' => 'visit',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListPages::class)
            ->assertTableActionsExistInOrder(['edit', 'copy', 'viewPublicPage', 'delete'])
            ->assertTableActionHasColor('edit', 'success', $page)
            ->assertTableActionHasColor('copy', 'success', $page)
            ->assertTableActionHasColor('viewPublicPage', 'gray', $page)
            ->assertTableActionHasColor('delete', 'danger', $page)
            ->assertTableActionHasUrl('viewPublicPage', $page->publicUrl(), $page)
            ->assertTableActionShouldOpenUrlInNewTab('viewPublicPage', $page);
    }

    public function test_pages_can_be_copied_when_table_loads_child_page_counts(): void
    {
        $parent = Page::query()->create([
            'title' => 'Resources',
            'slug' => 'resources',
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->id,
            'title' => 'Forms',
            'slug' => 'resources/forms',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListPages::class)
            ->callTableAction('copy', $parent)
            ->assertHasNoErrors();

        $copy = Page::query()
            ->whereKeyNot($parent->id)
            ->where('title', 'like', 'Resources (copy @ %')
            ->firstOrFail();

        $this->assertStringStartsWith('resources-copy-', $copy->slug);
        $this->assertDatabaseCount(Page::class, 3);
    }

    public function test_edit_pages_show_view_public_page_header_action(): void
    {
        $page = Page::query()->create([
            'title' => 'Visit',
            'slug' => 'visit',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditPage::class, ['record' => $page->getKey()])
            ->assertActionHasUrl('headerViewPublicPage', $page->publicUrl())
            ->assertActionShouldOpenUrlInNewTab('headerViewPublicPage');
    }

    public function test_create_redirects_to_edit_screen_after_create(): void
    {
        $component = Livewire::actingAs(User::factory()->create())
            ->test(CreatePage::class)
            ->assertSee('Publish at')
            ->assertSee('Expires at')
            ->set('data.title', 'Plan a Visit')
            ->set('data.slug', 'plan-a-visit')
            ->set('data.is_published', true)
            ->set('data.publish_at', '2026-06-10 08:00:00')
            ->set('data.expires_at', '2026-06-30 17:00:00')
            ->set('data.card_image_path', ['pages/card-images/plan-a-visit.jpg'])
            ->set('data.show_site_chrome', true)
            ->set('data.show_page_header', true)
            ->call('create')
            ->assertHasNoErrors();

        $page = Page::query()->where('slug', 'plan-a-visit')->firstOrFail();

        $this->assertSame('2026-06-10 08:00:00', $page->publish_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-30 17:00:00', $page->expires_at?->format('Y-m-d H:i:s'));
        $this->assertSame('pages/card-images/plan-a-visit.jpg', $page->card_image_path);

        $component->assertRedirect(PageResource::getUrl('edit', ['record' => $page]));
    }

    public function test_homepage_content_shows_view_public_page_action(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(HomepageContent::class)
            ->assertSee('View')
            ->assertDontSee('View Public Page')
            ->assertDontSee('View public page')
            ->assertActionHasUrl('viewPublicPage', route('home'))
            ->assertActionShouldOpenUrlInNewTab('viewPublicPage');
    }
}

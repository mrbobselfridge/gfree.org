<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\NavigationLinks\Pages\ListNavigationLinks;
use App\Models\NavigationLink;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class NavigationLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_navigation_respects_publish_and_expiration_dates(): void
    {
        NavigationLink::query()->create([
            'label' => 'Always Active',
            'url' => '/always-active',
            'location' => 'header',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'label' => 'Active Window',
            'url' => '/active-window',
            'location' => 'header',
            'sort_order' => 2,
            'publish_at' => now()->subHour(),
            'expires_at' => now()->addHour(),
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'label' => 'Future Link',
            'url' => '/future-link',
            'location' => 'header',
            'sort_order' => 3,
            'publish_at' => now()->addDay(),
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'label' => 'Expired Link',
            'url' => '/expired-link',
            'location' => 'header',
            'sort_order' => 4,
            'expires_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Always Active')
            ->assertSee('Active Window')
            ->assertDontSee('Future Link')
            ->assertDontSee('Expired Link');
    }

    public function test_header_navigation_renders_active_child_links_in_dropdowns(): void
    {
        $parent = NavigationLink::query()->create([
            'label' => 'Ministries',
            'url' => '/ministry',
            'location' => 'header',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'parent_id' => $parent->id,
            'label' => 'Kids',
            'url' => '/ministry/kids',
            'location' => 'header',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'parent_id' => $parent->id,
            'label' => 'Future Students',
            'url' => '/ministry/students',
            'location' => 'header',
            'sort_order' => 2,
            'publish_at' => now()->addDay(),
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-nav-toggle', false)
            ->assertSee('aria-controls="primary-navigation"', false)
            ->assertSee('data-subnav-toggle', false)
            ->assertSee('aria-controls="primary-navigation-submenu-0"', false)
            ->assertSee('data-subnav-panel', false)
            ->assertSee('Ministries')
            ->assertSee('Kids')
            ->assertDontSee('Future Students');
    }

    public function test_header_navigation_hides_links_to_inactive_matching_pages(): void
    {
        Page::query()->create([
            'title' => 'Visible Page',
            'slug' => 'visible-page',
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Absolute Visible Page',
            'slug' => 'absolute-visible-page',
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Future Page',
            'slug' => 'future-page',
            'publish_at' => now()->addDay(),
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Expired Page',
            'slug' => 'expired-page',
            'expires_at' => now()->subDay(),
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'is_published' => false,
        ]);

        foreach ([
            ['Visible Page Link', '/visible-page'],
            ['Absolute Visible Page Link', url('/absolute-visible-page')],
            ['Future Page Link', '/future-page'],
            ['Expired Page Link', '/expired-page'],
            ['Draft Page Link', '/draft-page'],
            ['System Route Link', '/announcements'],
            ['External Link', 'https://example.com/events'],
        ] as $index => [$label, $url]) {
            NavigationLink::query()->create([
                'label' => $label,
                'url' => $url,
                'location' => 'header',
                'sort_order' => $index + 1,
                'is_published' => true,
            ]);
        }

        $this->get('/')
            ->assertOk()
            ->assertSee('Visible Page Link')
            ->assertSee('Absolute Visible Page Link')
            ->assertSee('System Route Link')
            ->assertSee('External Link')
            ->assertDontSee('Future Page Link')
            ->assertDontSee('Expired Page Link')
            ->assertDontSee('Draft Page Link');
    }

    public function test_header_navigation_hides_child_links_to_inactive_matching_pages(): void
    {
        Page::query()->create([
            'title' => 'Kids Page',
            'slug' => 'kids-page',
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Future Students Page',
            'slug' => 'future-students-page',
            'publish_at' => now()->addDay(),
            'is_published' => true,
        ]);

        $parent = NavigationLink::query()->create([
            'label' => 'Ministries',
            'url' => '/ministries',
            'location' => 'header',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'parent_id' => $parent->id,
            'label' => 'Kids Page Link',
            'url' => '/kids-page',
            'location' => 'header',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'parent_id' => $parent->id,
            'label' => 'Future Students Page Link',
            'url' => '/future-students-page',
            'location' => 'header',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ministries')
            ->assertSee('Kids Page Link')
            ->assertDontSee('Future Students Page Link');
    }

    public function test_navigation_admin_shows_page_limit_statuses(): void
    {
        Page::query()->create([
            'title' => 'Live Page',
            'slug' => 'live-page',
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Windowed Page',
            'slug' => 'windowed-page',
            'publish_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Future Page',
            'slug' => 'future-page',
            'publish_at' => now()->addDay(),
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Expired Page',
            'slug' => 'expired-page',
            'expires_at' => now()->subDay(),
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'is_published' => false,
        ]);

        foreach ([
            ['Live Page Link', '/live-page'],
            ['Windowed Page Link', '/windowed-page'],
            ['Future Page Link', '/future-page'],
            ['Expired Page Link', '/expired-page'],
            ['Draft Page Link', '/draft-page'],
            ['Listing Link', '/announcements'],
        ] as $index => [$label, $url]) {
            NavigationLink::query()->create([
                'label' => $label,
                'url' => $url,
                'location' => 'header',
                'sort_order' => $index + 1,
                'is_published' => true,
            ]);
        }

        Livewire::actingAs(User::factory()->create())
            ->test(ListNavigationLinks::class)
            ->assertSee('Page live')
            ->assertSee('Page window active')
            ->assertSee('Hidden: page future')
            ->assertSee('Hidden: page expired')
            ->assertSee('Hidden: page draft')
            ->assertSee('No Page record uses /announcements');
    }

    public function test_header_navigation_does_not_render_default_give_link(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('concept-nav__give', false)
            ->assertDontSee('>Give</a>', false);
    }

    public function test_sermons_header_navigation_is_not_limited_to_five_links(): void
    {
        Http::fake([
            'youtube.com/feeds/videos.xml*' => Http::response('', 500),
        ]);

        foreach (range(1, 6) as $index) {
            NavigationLink::query()->create([
                'label' => "Header Link {$index}",
                'url' => "/header-link-{$index}",
                'location' => 'header',
                'sort_order' => $index,
                'is_published' => true,
            ]);
        }

        $this->get('/sermons')
            ->assertOk()
            ->assertSee('Header Link 1')
            ->assertSee('Header Link 5')
            ->assertSee('Header Link 6');
    }

    public function test_navigation_links_can_be_copied_from_admin_table(): void
    {
        $link = NavigationLink::query()->create([
            'label' => 'Visit',
            'url' => '/visit',
            'location' => 'header',
            'sort_order' => 4,
            'opens_in_new_tab' => false,
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListNavigationLinks::class)
            ->callTableAction('copy', $link)
            ->assertHasNoErrors();

        $this->assertDatabaseCount(NavigationLink::class, 2);
        $this->assertDatabaseHas(NavigationLink::class, [
            'url' => '/visit',
            'location' => 'header',
            'sort_order' => 4,
            'is_published' => true,
        ]);

        $copy = NavigationLink::query()
            ->whereKeyNot($link->id)
            ->firstOrFail();

        $this->assertStringStartsWith('Visit (copy @ ', $copy->label);
    }

    public function test_navigation_copy_keeps_long_labels_within_column_length(): void
    {
        $label = str_repeat('A', 255);

        $link = NavigationLink::query()->create([
            'label' => $label,
            'url' => '/long-label',
            'location' => 'header',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListNavigationLinks::class)
            ->callTableAction('copy', $link)
            ->assertHasNoErrors();

        $copy = NavigationLink::query()
            ->whereKeyNot($link->id)
            ->firstOrFail();

        $this->assertLessThanOrEqual(255, strlen($copy->label));
        $this->assertStringContainsString('(copy @ ', $copy->label);
    }

    public function test_copying_parent_navigation_link_copies_child_links(): void
    {
        $parent = NavigationLink::query()->create([
            'label' => 'Ministries',
            'url' => '/ministry',
            'location' => 'header',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'parent_id' => $parent->id,
            'label' => 'Kids',
            'url' => '/ministry/kids',
            'location' => 'header',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'parent_id' => $parent->id,
            'label' => 'Students',
            'url' => '/ministry/students',
            'location' => 'header',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListNavigationLinks::class)
            ->callTableAction('copy', $parent)
            ->assertHasNoErrors();

        $copy = NavigationLink::query()
            ->whereNull('parent_id')
            ->whereKeyNot($parent->id)
            ->firstOrFail();

        $this->assertStringStartsWith('Ministries (copy @ ', $copy->label);
        $this->assertSame(['Kids', 'Students'], $copy->children()->orderBy('sort_order')->pluck('label')->all());
        $this->assertSame(['/ministry/kids', '/ministry/students'], $copy->children()->orderBy('sort_order')->pluck('url')->all());
    }
}

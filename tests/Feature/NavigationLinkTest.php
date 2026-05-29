<?php

namespace Tests\Feature;

use App\Models\NavigationLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
            ->assertSee('Ministries')
            ->assertSee('Kids')
            ->assertDontSee('Future Students');
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
}

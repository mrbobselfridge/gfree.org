<?php

namespace Tests\Feature;

use App\Models\NavigationLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

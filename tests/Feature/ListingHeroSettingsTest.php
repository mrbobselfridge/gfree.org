<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingHeroSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_listing_paths_are_available_for_regular_pages(): void
    {
        $this->get('/announcements')->assertNotFound();
        $this->get('/bulletins')->assertNotFound();
        $this->get('/leadership')->assertNotFound();
        $this->get('/ministry')->assertNotFound();

        foreach (['announcements', 'bulletins', 'leadership', 'ministry'] as $slug) {
            Page::query()->create([
                'title' => str($slug)->headline()->toString(),
                'slug' => $slug,
                'intro' => 'A regular CMS page can now own this path.',
                'is_published' => true,
            ]);

            $this->get("/{$slug}")
                ->assertOk()
                ->assertSee(str($slug)->headline()->toString())
                ->assertSee('A regular CMS page can now own this path.');
        }
    }
}

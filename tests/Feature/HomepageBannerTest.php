<?php

namespace Tests\Feature;

use App\Models\HomepageBanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_homepage_banner_replaces_default_hero_content(): void
    {
        HomepageBanner::query()->create([
            'title' => 'Christmas Eve at gFree',
            'eyebrow' => 'Holiday services',
            'subtitle' => 'Join us for candlelight services.',
            'image_path' => 'homepage-banners/christmas.jpg',
            'button_label' => 'Reserve Seats',
            'button_url' => '/christmas',
            'secondary_button_label' => 'Invite a Friend',
            'secondary_button_url' => '/invite',
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Holiday services')
            ->assertSee('Christmas Eve at gFree')
            ->assertSee('Join us for candlelight services.')
            ->assertSee('/storage/homepage-banners/christmas.jpg')
            ->assertSee('Reserve Seats')
            ->assertSee('/christmas')
            ->assertSee('Invite a Friend')
            ->assertSee('/invite')
            ->assertDontSee('data-hero-previous', false)
            ->assertDontSee('data-hero-count', false);
    }

    public function test_homepage_banner_blank_optional_fields_fall_back_except_subtitle(): void
    {
        HomepageBanner::query()->create([
            'title' => 'Special Sunday',
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Welcome home')
            ->assertSee('Special Sunday')
            ->assertDontSee('A church family in central Pennsylvania')
            ->assertSee('data-hero-subtitle', false)
            ->assertSee('hidden', false)
            ->assertSee('images.unsplash.com')
            ->assertSee('Plan a Visit')
            ->assertSee('/new-here')
            ->assertSee('Watch Live')
            ->assertSee('/live');
    }

    public function test_homepage_uses_random_active_published_banner(): void
    {
        HomepageBanner::query()->create([
            'title' => 'Draft Banner',
            'is_published' => false,
        ]);

        HomepageBanner::query()->create([
            'title' => 'Future Banner',
            'starts_at' => now()->addDay(),
            'is_published' => true,
        ]);

        HomepageBanner::query()->create([
            'title' => 'Expired Banner',
            'ends_at' => now()->subDay(),
            'is_published' => true,
        ]);

        HomepageBanner::query()->create([
            'title' => 'Active Banner One',
            'is_published' => true,
        ]);

        HomepageBanner::query()->create([
            'title' => 'Active Banner Two',
            'is_published' => true,
        ]);

        $response = $this->get('/')->assertOk();

        $this->assertTrue(
            str_contains($response->content(), 'Active Banner One') ||
            str_contains($response->content(), 'Active Banner Two'),
        );

        $response
            ->assertSee('data-hero-previous', false)
            ->assertSee('data-hero-next', false)
            ->assertDontSee('data-hero-count', false)
            ->assertDontSee('1 / 2')
            ->assertSee('Active Banner One')
            ->assertSee('Active Banner Two')
            ->assertDontSee('Draft Banner')
            ->assertDontSee('Future Banner')
            ->assertDontSee('Expired Banner');
    }
}

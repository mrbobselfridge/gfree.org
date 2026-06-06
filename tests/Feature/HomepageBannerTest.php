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
            'title' => 'Christmas Eve at TwyxtCo',
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
            ->assertSee('Christmas Eve at TwyxtCo')
            ->assertSee('Join us for candlelight services.')
            ->assertSee('/storage/homepage-banners/christmas.jpg')
            ->assertSee('Reserve Seats')
            ->assertSee('/christmas')
            ->assertSee('Invite a Friend')
            ->assertSee('/invite')
            ->assertDontSee('data-hero-previous', false)
            ->assertDontSee('data-hero-count', false);
    }

    public function test_homepage_banner_blank_optional_fields_hide_buttons(): void
    {
        HomepageBanner::query()->create([
            'title' => 'Special Sunday',
            'is_published' => true,
        ]);

        $content = $this->get('/')
            ->assertOk()
            ->assertSee('Welcome home')
            ->assertSee('Special Sunday')
            ->assertDontSee('A church family in central Pennsylvania')
            ->assertSee('data-hero-subtitle', false)
            ->assertSee('hidden', false)
            ->assertSee('images.unsplash.com')
            ->assertDontSee('Plan a Visit')
            ->assertDontSee('Watch Live')
            ->content();

        $this->assertMatchesRegularExpression('/data-hero-primary\s+hidden/s', $content);
        $this->assertMatchesRegularExpression('/data-hero-secondary\s+hidden/s', $content);
    }

    public function test_secondary_homepage_banner_button_keeps_secondary_style_when_primary_is_blank(): void
    {
        HomepageBanner::query()->create([
            'title' => 'Watch This Sunday',
            'secondary_button_label' => 'Watch Live',
            'secondary_button_url' => '/watch',
            'is_published' => true,
        ]);

        $content = $this->get('/')
            ->assertOk()
            ->assertSee('Watch This Sunday')
            ->assertDontSee('Plan a Visit')
            ->content();

        $this->assertMatchesRegularExpression('/data-hero-primary\s+hidden/s', $content);
        $this->assertMatchesRegularExpression(
            '/href="\/watch"\s+class="concept-button concept-button--secondary"\s+data-hero-secondary\s*>[\s\n]*Watch Live/s',
            $content,
        );
    }

    public function test_primary_homepage_banner_button_shows_without_secondary_button(): void
    {
        HomepageBanner::query()->create([
            'title' => 'Plan Your Visit',
            'button_label' => 'Plan a Visit',
            'button_url' => '/visit',
            'is_published' => true,
        ]);

        $content = $this->get('/')
            ->assertOk()
            ->assertSee('Plan Your Visit')
            ->assertSee('Plan a Visit')
            ->assertDontSee('Watch Live')
            ->content();

        $this->assertMatchesRegularExpression(
            '/href="\/visit"\s+class="concept-button concept-button--primary"\s+data-hero-primary\s*>[\s\n]*Plan a Visit/s',
            $content,
        );
        $this->assertMatchesRegularExpression('/data-hero-secondary\s+hidden/s', $content);
    }

    public function test_external_homepage_banner_buttons_open_in_new_tabs(): void
    {
        HomepageBanner::query()->create([
            'title' => 'Register Online',
            'button_label' => 'Register',
            'button_url' => 'https://events.example.com/register',
            'secondary_button_label' => 'Details',
            'secondary_button_url' => '/details',
            'is_published' => true,
        ]);

        $content = $this->get('/')
            ->assertOk()
            ->assertSee('Register Online')
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->content();

        $this->assertMatchesRegularExpression(
            '/href="https:\/\/events\.example\.com\/register"\s+class="concept-button concept-button--primary"\s+data-hero-primary\s+target="_blank" rel="noopener noreferrer"/s',
            $content,
        );
        $this->assertStringNotContainsString('href="/details" target="_blank"', $content);
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

<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\HomepageBanners\Pages\CreateHomepageBanner;
use App\Filament\Admin\Resources\HomepageBanners\Pages\ListHomepageBanners;
use App\Filament\Admin\Resources\HomepageBanners\HomepageBannerResource;
use App\Models\HomepageBanner;
use App\Models\HomepageContent;
use App\Models\User;
use Filament\Forms\Components\Textarea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomepageBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_banner_form_uses_taxonomy_v2_labels(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateHomepageBanner::class)
            ->assertSee('Banner title')
            ->assertSee('Banner message')
            ->assertSee('Banner is live')
            ->assertSee('Primary button text')
            ->assertSee('Primary button destination')
            ->assertSee('Secondary button text')
            ->assertSee('Secondary button destination')
            ->assertSee('Banner image');
    }

    public function test_homepage_banner_message_uses_html_code_textarea(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateHomepageBanner::class)
            ->assertFormFieldExists('subtitle', function (Textarea $field): bool {
                $attributes = $field->getExtraInputAttributeBag()->getAttributes();

                return $field->getRows() === 3
                    && ($attributes['data-twyxtco-code-textarea'] ?? null) === 'true'
                    && ($attributes['data-twyxtco-code-language'] ?? null) === 'html';
            });
    }

    public function test_homepage_banner_title_links_to_edit_page_from_listing(): void
    {
        $banner = HomepageBanner::query()->create([
            'title' => 'Holiday services',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListHomepageBanners::class)
            ->assertTableColumnExists('title', fn ($column) => $column->getUrl() === HomepageBannerResource::getUrl('edit', ['record' => $banner]), $banner);
    }

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

    public function test_homepage_banner_message_can_render_html(): void
    {
        HomepageBanner::query()->create([
            'title' => 'Mission Sunday',
            'subtitle' => 'Join us for <strong>Mission Sunday</strong><br><a href="/missions">Meet the team</a>',
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('class="concept-hero__subtitle"', false)
            ->assertSee('<strong>Mission Sunday</strong>', false)
            ->assertSee('<a href="/missions">Meet the team</a>', false)
            ->assertDontSee('&lt;strong&gt;Mission Sunday&lt;/strong&gt;', false);
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
            ->assertDontSee('data-hero-auto', false)
            ->assertDontSee('data-hero-pause', false)
            ->assertDontSee('data-hero-count', false)
            ->assertDontSee('1 / 2')
            ->assertSee('Active Banner One')
            ->assertSee('Active Banner Two')
            ->assertDontSee('Draft Banner')
            ->assertDontSee('Future Banner')
            ->assertDontSee('Expired Banner');
    }

    public function test_homepage_banner_auto_rotation_renders_timing_and_pause_controls_when_enabled(): void
    {
        HomepageContent::query()->create([
            'hero_banners_auto_rotate' => true,
        ]);

        HomepageBanner::query()->create([
            'title' => 'Active Banner One',
            'is_published' => true,
        ]);

        HomepageBanner::query()->create([
            'title' => 'Active Banner Two',
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-hero-auto', false)
            ->assertSee('data-hero-interval="20000"', false)
            ->assertSee('data-hero-fade-duration="3000"', false)
            ->assertSee('data-hero-pause', false)
            ->assertSee('Pause')
            ->assertSee('data-hero-previous', false)
            ->assertSee('data-hero-next', false)
            ->assertSee('Active Banner One')
            ->assertSee('Active Banner Two');
    }

    public function test_homepage_banner_auto_rotation_uses_configured_timing_values(): void
    {
        HomepageContent::query()->create([
            'hero_banners_auto_rotate' => true,
            'hero_banners_rotation_delay_seconds' => 45,
            'hero_banners_fade_duration_seconds' => 6,
        ]);

        HomepageBanner::query()->create([
            'title' => 'Active Banner One',
            'is_published' => true,
        ]);

        HomepageBanner::query()->create([
            'title' => 'Active Banner Two',
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-hero-auto', false)
            ->assertSee('data-hero-interval="45000"', false)
            ->assertSee('data-hero-fade-duration="6000"', false);
    }
}

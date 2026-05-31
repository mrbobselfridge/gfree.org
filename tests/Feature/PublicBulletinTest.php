<?php

namespace Tests\Feature;

use App\Models\Bulletin;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBulletinTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulletins_listing_shows_published_bulletins_newest_first(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
        ]);

        Bulletin::query()->create([
            'title' => 'Older Bulletin',
            'bulletin_date' => '2026-05-24',
            'extracted_html' => '<p>Older content.</p>',
            'is_published' => true,
        ]);

        Bulletin::query()->create([
            'title' => 'Newest Bulletin',
            'bulletin_date' => '2026-05-31',
            'extracted_html' => '<p>Newest content.</p>',
            'is_published' => true,
        ]);

        Bulletin::query()->create([
            'title' => 'Draft Bulletin',
            'bulletin_date' => '2026-06-07',
            'extracted_html' => '<p>Draft content.</p>',
            'is_published' => false,
        ]);

        $response = $this->get('/bulletins')
            ->assertOk()
            ->assertSee('Bulletins')
            ->assertSee('Newest Bulletin')
            ->assertSee('Older Bulletin')
            ->assertDontSee('Draft Bulletin')
            ->assertSee('/bulletins/2026-05-31')
            ->assertSee('/bulletins/2026-05-24');

        $this->assertLessThan(
            strpos($response->getContent(), 'Older Bulletin'),
            strpos($response->getContent(), 'Newest Bulletin')
        );
    }

    public function test_bulletins_listing_uses_configured_hero_settings(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'bulletins_small_label' => 'This week',
            'bulletins_title' => 'Weekly Bulletins',
            'bulletins_subtitle' => '<p>Everything you need to <strong>follow along</strong>.</p>',
            'bulletins_image_path' => 'site-settings/bulletins/bulletins.jpg',
        ]);

        Bulletin::query()->create([
            'title' => 'Sunday Bulletin',
            'bulletin_date' => '2026-05-31',
            'extracted_html' => '<p>Bulletin content.</p>',
            'is_published' => true,
        ]);

        $this->get('/bulletins')
            ->assertOk()
            ->assertSee('This week')
            ->assertSee('Weekly Bulletins')
            ->assertSee('<strong>follow along</strong>', false)
            ->assertDontSee('&lt;strong&gt;follow along&lt;/strong&gt;', false)
            ->assertSee('/storage/site-settings/bulletins/bulletins.jpg')
            ->assertSee('page-hero--image');
    }

    public function test_bulletin_detail_uses_selected_date_slug_and_shows_html_and_pdf_link(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'bulletins_image_path' => 'site-settings/bulletins/bulletins.jpg',
        ]);

        Bulletin::query()->create([
            'title' => 'Sunday Bulletin',
            'bulletin_date' => '2026-05-31',
            'pdf_path' => 'bulletins/pdfs/sunday.pdf',
            'extracted_html' => '<h2>Welcome</h2><p>Join us this week.</p>',
            'is_published' => true,
        ]);

        $this->get('/bulletins/2026-05-31')
            ->assertOk()
            ->assertSee('Sunday Bulletin')
            ->assertSee('May 31, 2026')
            ->assertSee('/storage/site-settings/bulletins/bulletins.jpg')
            ->assertSee('page-hero--image')
            ->assertSee('<h2>Welcome</h2>', false)
            ->assertSee('Join us this week.')
            ->assertSee('/storage/bulletins/pdfs/sunday.pdf')
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false);
    }

    public function test_unpublished_bulletin_detail_is_not_public(): void
    {
        Bulletin::query()->create([
            'title' => 'Draft Bulletin',
            'bulletin_date' => '2026-05-31',
            'extracted_html' => '<p>Draft content.</p>',
            'is_published' => false,
        ]);

        $this->get('/bulletins/2026-05-31')
            ->assertNotFound();
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\SiteAlerts\Pages\CreateSiteAlert;
use App\Filament\Admin\Resources\SiteAlerts\Pages\ListSiteAlerts;
use App\Models\SiteAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_site_alerts_render_stacked_in_configured_order(): void
    {
        SiteAlert::query()->create([
            'label' => 'Second',
            'message' => 'Lower priority alert.',
            'sort_order' => 20,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
            'is_published' => true,
        ]);

        SiteAlert::query()->create([
            'label' => 'First',
            'message' => 'Higher priority alert.',
            'link_label' => 'Read more',
            'link_url' => '/news',
            'sort_order' => 10,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
            'is_published' => true,
        ]);

        SiteAlert::query()->create([
            'label' => 'Newest',
            'message' => 'Newest matching priority alert.',
            'sort_order' => 10,
            'created_at' => now(),
            'updated_at' => now(),
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('site-alert-stack', false)
            ->assertSeeInOrder(['Newest', 'Newest matching priority alert.', 'First', 'Higher priority alert.', 'Read more', 'Second', 'Lower priority alert.'])
            ->assertSee('href="'.url('/news').'"', false);
    }

    public function test_site_alerts_respect_publish_expiration_and_live_status(): void
    {
        foreach ([
            ['Live Alert', null, null, true],
            ['Future Alert', now()->addDay(), null, true],
            ['Expired Alert', null, now()->subDay(), true],
            ['Draft Alert', null, null, false],
        ] as [$message, $publishAt, $expiresAt, $isPublished]) {
            SiteAlert::query()->create([
                'message' => $message,
                'publish_at' => $publishAt,
                'expires_at' => $expiresAt,
                'is_published' => $isPublished,
            ]);
        }

        $this->get('/')
            ->assertOk()
            ->assertSee('Live Alert')
            ->assertDontSee('Future Alert')
            ->assertDontSee('Expired Alert')
            ->assertDontSee('Draft Alert');
    }

    public function test_site_alert_dismissal_metadata_is_individual_and_versioned(): void
    {
        $dismissible = SiteAlert::query()->create([
            'message' => 'Dismiss me.',
            'is_published' => true,
            'is_dismissible' => true,
        ]);

        $fixed = SiteAlert::query()->create([
            'message' => 'Do not dismiss me.',
            'is_published' => true,
            'is_dismissible' => false,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-site-alert-key="'.$dismissible->dismissalKey().'"', false)
            ->assertSee('data-site-alert-key="'.$fixed->dismissalKey().'"', false)
            ->assertSee('data-site-alert-dismiss', false)
            ->assertSee('Dismiss me.')
            ->assertSee('Do not dismiss me.');
    }

    public function test_no_site_alert_markup_renders_when_no_alerts_are_active(): void
    {
        SiteAlert::query()->create([
            'message' => 'Draft alert.',
            'is_published' => false,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('site-alert-stack', false)
            ->assertDontSee('Draft alert.');
    }

    public function test_site_alert_admin_can_create_alerts(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateSiteAlert::class)
            ->set('data.label', 'News Alert')
            ->set('data.message', 'Job posting available.')
            ->set('data.link_label', 'View posting')
            ->set('data.link_url', '/jobs')
            ->set('data.sort_order', 5)
            ->set('data.is_published', true)
            ->set('data.is_dismissible', true)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(SiteAlert::class, [
            'label' => 'News Alert',
            'message' => 'Job posting available.',
            'link_label' => 'View posting',
            'link_url' => '/jobs',
            'sort_order' => 5,
            'is_published' => true,
            'is_dismissible' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListSiteAlerts::class)
            ->assertSee('News Alert')
            ->assertSee('Job posting available.');
    }
}

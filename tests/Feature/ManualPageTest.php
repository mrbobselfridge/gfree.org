<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_describes_backups(): void
    {
        $this->get('/manual')
            ->assertOk()
            ->assertSee('Last updated: June 10, 2026')
            ->assertSee('Backups')
            ->assertSee('Database Backup')
            ->assertSee('Full Site Backup')
            ->assertSee('Archive Backup')
            ->assertSee('Use <strong>Run now</strong> before a major content cleanup, launch, or sitewide change.', false)
            ->assertSee('Restore caution');
    }

    public function test_manual_describes_redirect_pages(): void
    {
        $this->get('/manual')
            ->assertOk()
            ->assertSee('Redirect Pages')
            ->assertSee('Turn on <strong>Redirect this page</strong>.', false)
            ->assertSee('Use <strong>Temporary</strong> for most redirects unless the old URL has permanently moved.', false);
    }
}

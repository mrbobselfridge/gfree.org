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
}

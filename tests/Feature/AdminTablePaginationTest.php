<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Announcements\Pages\ListAnnouncements;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTablePaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_tables_default_to_fifty_records_per_page(): void
    {
        $component = Livewire::actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]))->test(ListAnnouncements::class);

        $this->assertSame(50, $component->instance()->getTable()->getDefaultPaginationPageOption());
    }
}

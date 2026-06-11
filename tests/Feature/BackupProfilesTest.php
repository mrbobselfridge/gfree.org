<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Backups;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\BackupProfiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BackupProfilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_profiles_define_database_and_full_commands(): void
    {
        $this->assertSame('backup:run --config=backup_database --only-db', BackupProfiles::command('database'));
        $this->assertSame('backup:run --config=backup_full', BackupProfiles::command('full'));
        $this->assertSame('backup:run --config=backup_archive', BackupProfiles::command('archive'));

        $profiles = BackupProfiles::all();

        $this->assertSame('Every 4 hours', $profiles->get('database')['schedule_label']);
        $this->assertSame('Daily at 01:00', $profiles->get('full')['schedule_label']);
        $this->assertSame('Weekly on Sunday at 02:00', $profiles->get('archive')['schedule_label']);
    }

    public function test_admin_can_view_backup_page_with_latest_backup_status(): void
    {
        Storage::fake('backups');

        $path = config('backup_full.backup.name').'/full-2026-06-10-01-00-00.zip';
        Storage::disk('backups')->put($path, 'backup zip');

        Livewire::actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]))
            ->test(Backups::class)
            ->assertSee('Full Site Backup')
            ->assertSee('Download latest')
            ->assertSee('Delete')
            ->assertSee('backups');

        $latest = BackupProfiles::latestBackup('full');

        $this->assertNotNull($latest);
        $this->assertSame($path, $latest['path']);
        $this->assertNotEmpty($latest['timestamp']);

        Livewire::actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]))
            ->test(Backups::class)
            ->assertSee('title="'.$latest['timestamp'].'"', false);
    }

    public function test_admin_can_delete_backup_file_with_confirmation(): void
    {
        Storage::fake('backups');

        $path = config('backup_full.backup.name').'/full-2026-06-10-01-00-00.zip';
        Storage::disk('backups')->put($path, 'backup zip');

        $latest = BackupProfiles::latestBackup('full');

        Livewire::actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]))
            ->test(Backups::class)
            ->callAction('deleteBackup', arguments: [
                'profile' => 'full',
                'disk' => 'backups',
                'path' => $latest['encoded_path'],
                'name' => $latest['name'],
            ])
            ->assertHasNoActionErrors();

        Storage::disk('backups')->assertMissing($path);
    }

    public function test_backup_download_requires_backup_access(): void
    {
        Storage::fake('backups');

        $path = config('backup_full.backup.name').'/full-2026-06-10-01-00-00.zip';
        Storage::disk('backups')->put($path, 'backup zip');

        $url = BackupProfiles::latestBackup('full')['download_url'];

        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => ['tools' => []],
        ]))
            ->get($url)
            ->assertForbidden();

        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => ['tools' => [AdminAccess::BACKUPS]],
        ]))
            ->get($url)
            ->assertOk()
            ->assertDownload('full-2026-06-10-01-00-00.zip');
    }
}

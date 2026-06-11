<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\FileDocuments\Pages\CreateFileDocument;
use App\Filament\Admin\Resources\FileDocuments\Pages\EditFileDocument;
use App\Filament\Admin\Resources\FileDocuments\FileDocumentResource;
use App\Filament\Admin\Resources\FileDocuments\RelationManagers\VersionsRelationManager;
use App\Models\FileDocument;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\FileLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FileLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_file_library_and_user_permission_in_content_group(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get('/admin/file-documents')
            ->assertOk()
            ->assertSee('File Library');

        $this->actingAs($admin)
            ->get('/admin/users/create')
            ->assertOk()
            ->assertSee('File Library');

        $this->assertArrayHasKey(AdminAccess::FILE_LIBRARY, AdminAccess::toolOptionsForGroup('Content'));
        $this->assertArrayNotHasKey(AdminAccess::FILE_LIBRARY, AdminAccess::toolOptionsForGroup('Sitewide'));
        $this->assertFalse(FileDocumentResource::shouldRegisterNavigation());
    }

    public function test_file_listing_is_available_from_media_library_tab(): void
    {
        FileDocument::query()->create([
            'title' => 'Connection Card',
            'file_name' => 'connection-card',
            'category' => 'Form',
            'visibility' => FileDocument::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]))
            ->get('/admin/media-library?library=files')
            ->assertOk()
            ->assertSee('Library:')
            ->assertSee('Image Gallery')
            ->assertSee('File Listing')
            ->assertSee('Connection Card')
            ->assertSee('New file')
            ->assertDontSee('Uploaded images');
    }

    public function test_editor_needs_file_library_access(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [],
                'records' => [],
            ],
        ]);

        $this->actingAs($editor)
            ->get('/admin/file-documents')
            ->assertForbidden();

        $editor->forceFill([
            'admin_permissions' => [
                'tools' => [AdminAccess::FILE_LIBRARY],
                'records' => [],
            ],
        ])->save();

        $this->actingAs($editor)
            ->get('/admin/file-documents')
            ->assertOk();

        $this->actingAs($editor)
            ->get('/admin/media-library?library=files')
            ->assertOk()
            ->assertSee('File Listing')
            ->assertDontSee('Image Gallery');
    }

    public function test_create_file_document_uploads_file_and_serves_public_stable_link(): void
    {
        Storage::fake(FileLibrary::DISK);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        Livewire::actingAs($admin)
            ->test(CreateFileDocument::class)
            ->set('data.title', 'Connection Card')
            ->set('data.file_name', 'connection-card')
            ->set('data.category', 'Form')
            ->set('data.visibility', FileDocument::VISIBILITY_PUBLIC)
            ->set('data.pending_upload', UploadedFile::fake()->create('connection-card.pdf', 25, 'application/pdf'))
            ->set('data.pending_original_name', 'connection-card.pdf')
            ->call('create')
            ->assertHasNoErrors();

        $document = FileDocument::query()->where('file_name', 'connection-card')->firstOrFail();

        $this->assertSame('Connection Card', $document->title);
        $this->assertSame($admin->getKey(), $document->uploaded_by_id);
        $this->assertNotNull($document->current_version_id);
        $this->assertSame('connection-card.pdf', $document->currentVersion->original_name);
        Storage::disk(FileLibrary::DISK)->assertExists($document->currentVersion->path);

        $this->get('/files/connection-card')
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=connection-card.pdf');
    }

    public function test_private_files_are_not_available_on_public_route_but_admin_can_download(): void
    {
        Storage::fake(FileLibrary::DISK);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $document = FileDocument::query()->create([
            'title' => 'Internal Policy',
            'file_name' => 'internal-policy',
            'category' => 'Policy',
            'visibility' => FileDocument::VISIBILITY_PRIVATE,
            'uploaded_by_id' => $admin->getKey(),
            'updated_by_id' => $admin->getKey(),
        ]);

        Storage::disk(FileLibrary::DISK)->put('file-library/documents/internal-policy.pdf', 'private document');
        FileLibrary::createVersion($document, 'file-library/documents/internal-policy.pdf', 'internal-policy.pdf', $admin);

        $this->get('/files/internal-policy')
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.files.download', ['fileDocument' => $document]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=internal-policy.pdf');
    }

    public function test_edit_file_document_shows_current_file_and_replace_upload(): void
    {
        Storage::fake(FileLibrary::DISK);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $document = FileDocument::query()->create([
            'title' => 'Connection Card',
            'file_name' => 'connection-card',
            'category' => 'Form',
            'visibility' => FileDocument::VISIBILITY_PUBLIC,
        ]);

        Storage::disk(FileLibrary::DISK)->put('file-library/documents/connection-card.pdf', 'document');
        FileLibrary::createVersion($document, 'file-library/documents/connection-card.pdf', 'connection-card.pdf', $admin);

        Livewire::actingAs($admin)
            ->test(EditFileDocument::class, ['record' => $document->getKey()])
            ->assertSet('data.current_file', fn (array $state): bool => in_array('file-library/documents/connection-card.pdf', $state, true));

        $this->actingAs($admin)
            ->get("/admin/file-documents/{$document->getKey()}/edit")
            ->assertOk()
            ->assertSee('Current file')
            ->assertSee('Replace file');
    }

    public function test_versions_can_be_restored_and_files_are_deleted_with_document(): void
    {
        Storage::fake(FileLibrary::DISK);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $document = FileDocument::query()->create([
            'title' => 'Poster',
            'file_name' => 'poster',
            'category' => 'Poster',
            'visibility' => FileDocument::VISIBILITY_PUBLIC,
        ]);

        Storage::disk(FileLibrary::DISK)->put('file-library/documents/poster-v1.pdf', 'v1');
        Storage::disk(FileLibrary::DISK)->put('file-library/documents/poster-v2.pdf', 'v2');

        $versionOne = FileLibrary::createVersion($document, 'file-library/documents/poster-v1.pdf', 'poster-v1.pdf', $admin);
        $versionTwo = FileLibrary::createVersion($document->refresh(), 'file-library/documents/poster-v2.pdf', 'poster-v2.pdf', $admin);

        $this->assertSame($versionTwo->getKey(), $document->refresh()->current_version_id);

        FileLibrary::makeCurrent($document, $versionOne, $admin);

        $this->assertSame($versionOne->getKey(), $document->refresh()->current_version_id);

        $document->delete();

        Storage::disk(FileLibrary::DISK)->assertMissing('file-library/documents/poster-v1.pdf');
        Storage::disk(FileLibrary::DISK)->assertMissing('file-library/documents/poster-v2.pdf');
    }

    public function test_old_file_versions_can_be_deleted_from_version_history(): void
    {
        Storage::fake(FileLibrary::DISK);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $document = FileDocument::query()->create([
            'title' => 'Poster',
            'file_name' => 'poster',
            'category' => 'Poster',
            'visibility' => FileDocument::VISIBILITY_PUBLIC,
        ]);

        Storage::disk(FileLibrary::DISK)->put('file-library/documents/poster-v1.pdf', 'v1');
        Storage::disk(FileLibrary::DISK)->put('file-library/documents/poster-v2.pdf', 'v2');

        $oldVersion = FileLibrary::createVersion($document, 'file-library/documents/poster-v1.pdf', 'poster-v1.pdf', $admin);
        $currentVersion = FileLibrary::createVersion($document->refresh(), 'file-library/documents/poster-v2.pdf', 'poster-v2.pdf', $admin);

        Livewire::actingAs($admin)
            ->test(VersionsRelationManager::class, [
                'ownerRecord' => $document->refresh(),
                'pageClass' => EditFileDocument::class,
            ])
            ->assertTableActionVisible('deleteVersion', $oldVersion)
            ->assertTableActionHidden('deleteVersion', $currentVersion)
            ->callTableAction('deleteVersion', $oldVersion)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('file_document_versions', ['id' => $oldVersion->getKey()]);
        $this->assertDatabaseHas('file_document_versions', ['id' => $currentVersion->getKey()]);
        $this->assertSame($currentVersion->getKey(), $document->refresh()->current_version_id);
        Storage::disk(FileLibrary::DISK)->assertMissing('file-library/documents/poster-v1.pdf');
        Storage::disk(FileLibrary::DISK)->assertExists('file-library/documents/poster-v2.pdf');
    }
}

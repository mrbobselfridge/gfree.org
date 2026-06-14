<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\FileCategories\Pages\CreateFileCategory;
use App\Filament\Admin\Resources\FileCategories\Pages\EditFileCategory;
use App\Filament\Admin\Resources\FileCategories\Pages\ListFileCategories;
use App\Filament\Admin\Resources\FileCategories\FileCategoryResource;
use App\Filament\Admin\Resources\FileDocuments\Pages\CreateFileDocument;
use App\Filament\Admin\Resources\FileDocuments\Pages\EditFileDocument;
use App\Filament\Admin\Resources\FileDocuments\FileDocumentResource;
use App\Filament\Admin\Resources\FileDocuments\RelationManagers\VersionsRelationManager;
use App\Models\FileCategory;
use App\Models\FileDocument;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\FileLibrary;
use App\Support\MediaUsage;
use App\Support\OpenAiFileDocumentExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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
        $this->assertTrue(FileDocumentResource::shouldRegisterNavigation());
    }

    public function test_file_categories_are_managed_from_admin(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get('/admin/file-categories')
            ->assertOk()
            ->assertSee('File Categories');

        $this->assertFalse(FileCategoryResource::shouldRegisterNavigation());

        $listBreadcrumbs = Livewire::actingAs($admin)
            ->test(ListFileCategories::class)
            ->instance()
            ->getBreadcrumbs();

        $this->assertSame(FileDocumentResource::getBreadcrumb(), $listBreadcrumbs[FileDocumentResource::getUrl()]);
        $this->assertSame(FileCategoryResource::getBreadcrumb(), $listBreadcrumbs[FileCategoryResource::getUrl()]);
        $this->assertSame(['File Library', 'File Categories', 'List'], array_values($listBreadcrumbs));

        $createBreadcrumbs = Livewire::actingAs($admin)
            ->test(CreateFileCategory::class)
            ->instance()
            ->getBreadcrumbs();

        $this->assertSame(FileDocumentResource::getBreadcrumb(), $createBreadcrumbs[FileDocumentResource::getUrl()]);
        $this->assertSame(FileCategoryResource::getBreadcrumb(), $createBreadcrumbs[FileCategoryResource::getUrl()]);
        $this->assertSame(['File Library', 'File Categories', 'Create'], array_values($createBreadcrumbs));

        Livewire::actingAs($admin)
            ->test(CreateFileCategory::class)
            ->assertFormFieldExists('extraction_instructions')
            ->set('data.name', 'Volunteer Packet')
            ->set('data.sort_order', 15)
            ->set('data.extraction_instructions', 'Only extract volunteer signup steps.')
            ->call('create')
            ->assertHasNoErrors();

        $category = FileCategory::query()->where('name', 'Volunteer Packet')->firstOrFail();

        $this->assertSame(15, $category->sort_order);
        $this->assertSame('Only extract volunteer signup steps.', $category->extraction_instructions);
        $this->assertArrayHasKey('Volunteer Packet', FileCategory::options());

        $editBreadcrumbs = Livewire::actingAs($admin)
            ->test(EditFileCategory::class, ['record' => $category->getKey()])
            ->instance()
            ->getBreadcrumbs();

        $this->assertSame(FileDocumentResource::getBreadcrumb(), $editBreadcrumbs[FileDocumentResource::getUrl()]);
        $this->assertSame(FileCategoryResource::getBreadcrumb(), $editBreadcrumbs[FileCategoryResource::getUrl()]);
        $this->assertSame('Volunteer Packet', $editBreadcrumbs[FileCategoryResource::getUrl('edit', ['record' => $category])]);
        $this->assertSame(['File Library', 'File Categories', 'Volunteer Packet', 'Edit'], array_values($editBreadcrumbs));

        Livewire::actingAs($admin)
            ->test(ListFileCategories::class)
            ->callTableAction('delete', $category)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('file_categories', ['name' => 'Volunteer Packet']);
        $this->assertArrayNotHasKey('Volunteer Packet', FileCategory::options());
    }

    public function test_file_listing_is_available_from_file_library_resource(): void
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
            ->get('/admin/file-documents')
            ->assertOk()
            ->assertSee('Connection Card')
            ->assertSee('/admin/file-categories', false)
            ->assertSee('Categories')
            ->assertSee('/admin/file-documents/create', false);

        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]))
            ->get('/admin/media-library')
            ->assertOk()
            ->assertSee('Uploaded images')
            ->assertSee("mountAction('uploadImages')", false)
            ->assertDontSee('Connection Card')
            ->assertDontSee("mountAction('createFile')", false);
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
            ->get('/admin/media-library')
            ->assertForbidden();

        $this->actingAs($editor)
            ->get('/admin/file-categories')
            ->assertOk();
    }

    public function test_create_file_document_uploads_file_and_serves_public_stable_link(): void
    {
        Storage::fake(FileLibrary::DISK);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $parent = Page::query()->create([
            'title' => 'Resources',
            'slug' => 'resources',
            'is_published' => true,
        ]);
        $publishAt = now()->subMinute()->startOfMinute();

        $component = Livewire::actingAs($admin)
            ->test(CreateFileDocument::class)
            ->assertFormFieldExists('category')
            ->assertFormFieldExists('parent_page_id')
            ->assertFormFieldExists('card_image_path')
            ->assertFormFieldExists('is_published')
            ->assertFormFieldExists('publish_at')
            ->assertFormFieldExists('expires_at')
            ->assertSchemaComponentExists('created_at')
            ->assertSchemaComponentExists('updated_at')
            ->assertFormFieldDoesNotExist('description')
            ->assertFormFieldDoesNotExist('tags')
            ->set('data.title', 'Connection Card')
            ->set('data.file_name', 'connection-card')
            ->set('data.category', 'Form')
            ->set('data.parent_page_id', $parent->getKey())
            ->set('data.card_image_path', ['file-documents/card-images/connection-card.jpg'])
            ->set('data.is_published', true)
            ->set('data.visibility', FileDocument::VISIBILITY_PUBLIC)
            ->set('data.publish_at', $publishAt)
            ->set('data.pending_upload', UploadedFile::fake()->create('connection-card.pdf', 25, 'application/pdf'))
            ->set('data.pending_original_name', 'connection-card.pdf')
            ->call('create')
            ->assertHasNoErrors();

        $document = FileDocument::query()->where('file_name', 'connection-card')->firstOrFail();

        $component->assertRedirect(FileDocumentResource::getUrl('edit', ['record' => $document]));

        $this->assertSame('Connection Card', $document->title);
        $this->assertSame('Form', $document->category);
        $this->assertTrue($document->parentPage->is($parent));
        $this->assertSame('file-documents/card-images/connection-card.jpg', $document->card_image_path);
        $this->assertStringContainsString('/storage/file-documents/card-images/connection-card.jpg', $document->cardImageUrl());
        $this->assertTrue($parent->fileDocuments()->first()->is($document));
        $this->assertTrue($document->is_published);
        $this->assertTrue($document->publish_at->equalTo($publishAt));
        $this->assertSame($admin->getKey(), $document->uploaded_by_id);
        $this->assertNotNull($document->current_version_id);
        $this->assertSame('connection-card.pdf', $document->currentVersion->original_name);
        Storage::disk(FileLibrary::DISK)->assertExists($document->currentVersion->path);

        $this->get('/files/connection-card')
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=connection-card.pdf');
    }

    public function test_private_published_files_require_a_user_account_on_the_public_route(): void
    {
        Storage::fake(FileLibrary::DISK);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $user = User::factory()->create();

        $document = FileDocument::query()->create([
            'title' => 'Internal Policy',
            'file_name' => 'internal-policy',
            'category' => 'Policy',
            'is_published' => true,
            'visibility' => FileDocument::VISIBILITY_PRIVATE,
            'uploaded_by_id' => $admin->getKey(),
            'updated_by_id' => $admin->getKey(),
        ]);

        Storage::disk(FileLibrary::DISK)->put('file-library/documents/internal-policy.pdf', 'private document');
        FileLibrary::createVersion($document, 'file-library/documents/internal-policy.pdf', 'internal-policy.pdf', $admin);

        $this->assertSame(route('files.show', ['fileName' => 'internal-policy']), $document->refresh()->publicUrl());

        $this->get('/files/internal-policy')
            ->assertRedirect(route('filament.admin.auth.login'));

        $this->actingAs($user)
            ->get('/files/internal-policy')
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=internal-policy.pdf');

        $this->actingAs($admin)
            ->get(route('admin.files.download', ['fileDocument' => $document]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=internal-policy.pdf');
    }

    public function test_unpublished_files_are_not_available_on_the_public_route(): void
    {
        Storage::fake(FileLibrary::DISK);

        $user = User::factory()->create();

        $document = FileDocument::query()->create([
            'title' => 'Draft Policy',
            'file_name' => 'draft-policy',
            'category' => 'Policy',
            'is_published' => false,
            'visibility' => FileDocument::VISIBILITY_PUBLIC,
        ]);

        Storage::disk(FileLibrary::DISK)->put('file-library/documents/draft-policy.pdf', 'draft document');
        FileLibrary::createVersion($document, 'file-library/documents/draft-policy.pdf', 'draft-policy.pdf', $user);

        $this->assertNull($document->refresh()->publicUrl());

        $this->get('/files/draft-policy')
            ->assertNotFound();

        $this->actingAs($user)
            ->get('/files/draft-policy')
            ->assertNotFound();
    }

    public function test_future_publish_date_keeps_file_unavailable_until_the_date(): void
    {
        Storage::fake(FileLibrary::DISK);

        $user = User::factory()->create();

        $document = FileDocument::query()->create([
            'title' => 'Scheduled Policy',
            'file_name' => 'scheduled-policy',
            'category' => 'Policy',
            'is_published' => true,
            'visibility' => FileDocument::VISIBILITY_PUBLIC,
            'publish_at' => now()->addDay(),
        ]);

        Storage::disk(FileLibrary::DISK)->put('file-library/documents/scheduled-policy.pdf', 'scheduled document');
        FileLibrary::createVersion($document, 'file-library/documents/scheduled-policy.pdf', 'scheduled-policy.pdf', $user);

        $this->assertNull($document->refresh()->publicUrl());

        $this->get('/files/scheduled-policy')
            ->assertNotFound();
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
            ->assertFormFieldExists('category')
            ->assertFormFieldExists('parent_page_id')
            ->assertFormFieldExists('card_image_path')
            ->assertFormFieldExists('is_published')
            ->assertFormFieldExists('publish_at')
            ->assertFormFieldExists('expires_at')
            ->assertSchemaComponentExists('created_at')
            ->assertSchemaComponentExists('updated_at')
            ->assertFormFieldDoesNotExist('description')
            ->assertFormFieldDoesNotExist('tags')
            ->assertSet('data.current_file', fn (array $state): bool => in_array('file-library/documents/connection-card.pdf', $state, true));

        $this->actingAs($admin)
            ->get("/admin/file-documents/{$document->getKey()}/edit")
            ->assertOk()
            ->assertSee('Current file')
            ->assertSee('Replace file');
    }

    public function test_file_document_card_image_url_falls_back_to_default_file_image(): void
    {
        $document = FileDocument::query()->create([
            'title' => 'Connection Card',
            'file_name' => 'connection-card',
            'category' => 'Form',
            'visibility' => FileDocument::VISIBILITY_PUBLIC,
        ]);

        $this->assertSame(asset(FileDocument::DEFAULT_CARD_IMAGE_PATH), $document->cardImageUrl());
    }

    public function test_file_document_card_image_is_tracked_by_media_usage(): void
    {
        $document = FileDocument::query()->create([
            'title' => 'Connection Card',
            'file_name' => 'connection-card',
            'category' => 'Form',
            'card_image_path' => 'file-documents/card-images/connection-card.jpg',
            'visibility' => FileDocument::VISIBILITY_PUBLIC,
        ]);

        $usage = MediaUsage::forImages(['file-documents/card-images/connection-card.jpg']);

        $this->assertSame("File: {$document->title}", $usage['file-documents/card-images/connection-card.jpg'][0]['label']);
        $this->assertSame('File card image', $usage['file-documents/card-images/connection-card.jpg'][0]['detail']);

        $updated = MediaUsage::replaceImagePath(
            'file-documents/card-images/connection-card.jpg',
            'file-documents/card-images/updated-card.jpg',
        );

        $this->assertSame(1, $updated);
        $this->assertSame('file-documents/card-images/updated-card.jpg', $document->refresh()->card_image_path);
    }

    public function test_edit_file_document_refreshes_view_and_download_actions_after_save(): void
    {
        Storage::fake(FileLibrary::DISK);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $document = FileDocument::query()->create([
            'title' => 'Connection Card',
            'file_name' => 'connection-card',
            'category' => 'Form',
            'is_published' => true,
            'visibility' => FileDocument::VISIBILITY_PUBLIC,
        ]);

        Storage::disk(FileLibrary::DISK)->put('file-library/documents/connection-card.pdf', 'document');
        FileLibrary::createVersion($document, 'file-library/documents/connection-card.pdf', 'connection-card.pdf', $admin);

        Livewire::actingAs($admin)
            ->test(EditFileDocument::class, ['record' => $document->getKey()])
            ->assertActionHasLabel('downloadCurrentFile', 'Download')
            ->assertActionHasUrl('downloadCurrentFile', route('admin.files.download', ['fileDocument' => $document]))
            ->assertActionHasUrl('headerViewPublicPage', route('files.show', ['fileName' => 'connection-card']))
            ->set('data.file_name', 'internal-policy')
            ->set('data.visibility', FileDocument::VISIBILITY_PRIVATE)
            ->call('save')
            ->assertHasNoErrors()
            ->assertActionHasLabel('downloadCurrentFile', 'Download')
            ->assertActionHasUrl('downloadCurrentFile', route('admin.files.download', ['fileDocument' => $document]))
            ->assertActionHasUrl('headerViewPublicPage', route('files.show', ['fileName' => 'internal-policy']))
            ->set('data.is_published', 0)
            ->call('save')
            ->assertHasNoErrors()
            ->assertActionHasLabel('downloadCurrentFile', 'Download')
            ->assertActionHasUrl('downloadCurrentFile', route('admin.files.download', ['fileDocument' => $document]))
            ->assertActionDoesNotExist('headerViewPublicPage');
    }

    public function test_openai_file_document_extractor_sends_saved_file_and_category_instructions(): void
    {
        Storage::fake(FileLibrary::DISK);
        Storage::disk(FileLibrary::DISK)->put('file-library/documents/bulletin.pdf', '%PDF-1.4 test bulletin');

        SiteSetting::query()->updateOrCreate([
            'church_name' => 'TwyxtCo Church',
        ], [
            'openai_api_key' => 'test-key',
            'openai_bulletin_model' => 'gpt-4o-mini',
        ]);

        FileCategory::query()->updateOrCreate([
            'name' => 'Bulletin',
        ], [
            'sort_order' => 10,
            'extraction_instructions' => 'Only extract bulletin announcements.',
        ]);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output_text' => '<h2>Sunday Bulletin</h2><p>Welcome.</p>',
            ]),
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $document = FileDocument::query()->create([
            'title' => 'Sunday Bulletin',
            'file_name' => 'sunday-bulletin',
            'category' => 'Bulletin',
        ]);

        FileLibrary::createVersion($document, 'file-library/documents/bulletin.pdf', 'bulletin.pdf', $admin);

        $html = app(OpenAiFileDocumentExtractor::class)->extract($document->refresh());

        $this->assertSame('<h2>Sunday Bulletin</h2><p>Welcome.</p>', $html);

        Http::assertSent(function (Request $request): bool {
            $payload = json_encode($request->data()) ?: '';

            return $request->url() === 'https://api.openai.com/v1/responses'
                && str_contains($payload, '"model"')
                && str_contains($payload, '"type":"input_file"')
                && str_contains($payload, '"filename":"sunday-bulletin.pdf"')
                && str_contains($payload, ';base64,')
                && str_contains($payload, 'Only extract bulletin announcements.')
                && str_contains($payload, 'File title: Sunday Bulletin');
        });
    }

    public function test_extract_file_content_action_places_accepted_html_into_optional_content(): void
    {
        Storage::fake(FileLibrary::DISK);
        Storage::disk(FileLibrary::DISK)->put('file-library/documents/bulletin.pdf', '%PDF-1.4 test bulletin');

        SiteSetting::query()->updateOrCreate([
            'church_name' => 'TwyxtCo Church',
        ], [
            'openai_api_key' => 'test-key',
            'openai_bulletin_model' => 'gpt-4o-mini',
        ]);

        FileCategory::query()->updateOrCreate([
            'name' => 'Bulletin',
        ], [
            'sort_order' => 10,
            'extraction_instructions' => 'Extract bulletin details.',
        ]);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output_text' => '<h2>Extracted Bulletin</h2><p>Join us this week.</p>',
            ]),
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $document = FileDocument::query()->create([
            'title' => 'Sunday Bulletin',
            'file_name' => 'sunday-bulletin',
            'category' => 'Bulletin',
            'content' => '<p>Old content.</p>',
        ]);

        FileLibrary::createVersion($document, 'file-library/documents/bulletin.pdf', 'bulletin.pdf', $admin);

        Livewire::actingAs($admin)
            ->test(EditFileDocument::class, ['record' => $document->getKey()])
            ->assertActionExists('extractFileContent')
            ->callAction('extractFileContent')
            ->assertHasNoActionErrors();

        $this->assertSame(
            '<h2>Extracted Bulletin</h2><p>Join us this week.</p>',
            $document->refresh()->content,
        );
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

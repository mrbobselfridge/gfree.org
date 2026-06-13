<?php

namespace Tests\Feature;

use App\Filament\Admin\Forms\RichContentPlugins\FileLibraryLinkPlugin;
use App\Filament\Admin\Forms\RichEditorDefaults;
use App\Models\FileDocument;
use App\Models\User;
use App\Support\FileLibrary;
use App\Support\RichTextFileLibrary;
use Filament\Forms\Components\RichEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RichTextFileLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_rich_editor_defaults_include_file_library_link_tool(): void
    {
        $editor = RichEditorDefaults::configure(RichEditor::make('body'));
        $plugins = (fn (): array => $this->plugins)->call($editor);
        $toolbarButtons = (fn (): array => $this->toolbarButtons)->call($editor);
        $fileLibraryPlugins = array_filter(
            $plugins,
            fn (object $plugin): bool => $plugin instanceof FileLibraryLinkPlugin,
        );

        $this->assertNotEmpty($fileLibraryPlugins);
        $this->assertContainsOnlyInstancesOf(FileLibraryLinkPlugin::class, $fileLibraryPlugins);
        $this->assertContains('fileLibrary', collect($toolbarButtons)->flatten()->all());

        $plugin = new FileLibraryLinkPlugin;

        $this->assertSame('fileLibrary', $plugin->getEditorTools()[0]->getName());
        $this->assertSame('fileLibrary', $plugin->getEditorActions()[0]->getName());
    }

    public function test_file_library_rich_text_options_only_include_public_active_files(): void
    {
        Storage::fake(FileLibrary::DISK);

        $publicDocument = $this->documentWithVersion('Connection Card', 'connection-card', FileDocument::VISIBILITY_PUBLIC);
        $privateDocument = $this->documentWithVersion('Internal Policy', 'internal-policy', FileDocument::VISIBILITY_PRIVATE);
        $expiredDocument = $this->documentWithVersion('Old Poster', 'old-poster', FileDocument::VISIBILITY_PUBLIC, now()->subDay());
        $unpublishedDocument = $this->documentWithVersion('Draft Packet', 'draft-packet', FileDocument::VISIBILITY_PUBLIC, null, false);
        $scheduledDocument = $this->documentWithVersion('Scheduled Packet', 'scheduled-packet', FileDocument::VISIBILITY_PUBLIC, null, true, now()->addDay());
        $missingVersionDocument = FileDocument::query()->create([
            'title' => 'No File Yet',
            'file_name' => 'no-file-yet',
            'category' => 'Other',
            'is_published' => true,
            'visibility' => FileDocument::VISIBILITY_PUBLIC,
        ]);

        $options = RichTextFileLibrary::publicDocumentOptions();

        $this->assertArrayHasKey($publicDocument->getKey(), $options);
        $this->assertArrayNotHasKey($privateDocument->getKey(), $options);
        $this->assertArrayNotHasKey($expiredDocument->getKey(), $options);
        $this->assertArrayNotHasKey($unpublishedDocument->getKey(), $options);
        $this->assertArrayNotHasKey($scheduledDocument->getKey(), $options);
        $this->assertArrayNotHasKey($missingVersionDocument->getKey(), $options);
    }

    public function test_rich_text_upload_creates_public_file_library_document(): void
    {
        Storage::fake(FileLibrary::DISK);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        Storage::disk(FileLibrary::DISK)->put('file-library/documents/new-form.pdf', 'document');

        $document = RichTextFileLibrary::resolveDocument([
            'upload' => 'file-library/documents/new-form.pdf',
            'upload_original_name' => 'new-form.pdf',
            'new_title' => 'New Form',
            'new_category' => 'Form',
        ], $admin);

        $this->assertInstanceOf(FileDocument::class, $document);
        $this->assertSame('New Form', $document->title);
        $this->assertSame('form-new-form', $document->file_name);
        $this->assertSame('Form', $document->category);
        $this->assertTrue($document->is_published);
        $this->assertSame(FileDocument::VISIBILITY_PUBLIC, $document->visibility);
        $this->assertSame($admin->getKey(), $document->uploaded_by_id);
        $this->assertSame(route('files.show', ['fileName' => 'form-new-form']), $document->publicUrl());
        $this->assertSame('form-new-form.pdf', $document->currentVersion->original_name);
        Storage::disk(FileLibrary::DISK)->assertExists($document->currentVersion->path);
    }

    private function documentWithVersion(string $title, string $fileName, string $visibility, mixed $expiresAt = null, bool $isPublished = true, mixed $publishAt = null): FileDocument
    {
        $document = FileDocument::query()->create([
            'title' => $title,
            'file_name' => $fileName,
            'category' => 'Other',
            'is_published' => $isPublished,
            'visibility' => $visibility,
            'publish_at' => $publishAt,
            'expires_at' => $expiresAt,
        ]);

        $path = "file-library/documents/{$fileName}.pdf";
        Storage::disk(FileLibrary::DISK)->put($path, $title);
        FileLibrary::createVersion($document, $path, "{$fileName}.pdf");

        return $document->refresh();
    }
}

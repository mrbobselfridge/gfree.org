<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\MediaLibrary as MediaLibraryPage;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Models\Announcement;
use App\Models\MediaImageMetadata;
use App\Models\Page;
use App\Models\User;
use App\Support\MediaLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MediaLibraryAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_browse_uploaded_images(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('picnic.jpg', 1200, 630)
            ->storeAs('announcements', 'picnic.jpg', 'public');

        UploadedFile::fake()
            ->image('unused.jpg', 1200, 630)
            ->storeAs('announcements', 'unused.jpg', 'public');

        Announcement::query()->create([
            'title' => 'Church Picnic',
            'slug' => 'church-picnic',
            'image_path' => 'announcements/picnic.jpg',
            'is_published' => true,
        ]);

        UploadedFile::fake()
            ->create('bulletin.pdf', 50, 'application/pdf')
            ->storeAs('bulletins/pdfs', 'bulletin.pdf', 'public');

        $this->actingAs(User::factory()->create())
            ->get('/admin/media-library')
            ->assertOk()
            ->assertSee('Images')
            ->assertSee('Uploaded images')
            ->assertSee('picnic.jpg')
            ->assertSee('announcements/picnic.jpg')
            ->assertSee('Announcement: Church Picnic | Announcement image', false)
            ->assertSee('An: Church Picnic | Announceme..')
            ->assertSee('unused.jpg')
            ->assertSee('Unused')
            ->assertSee("mountAction('uploadImages')", false)
            ->assertDontSee("mountAction('createFile')", false)
            ->assertSee('title="Add"', false)
            ->assertSee('wire:partial="action-modals"', false)
            ->assertSee('title="Open"', false)
            ->assertSee('title="Download"', false)
            ->assertSee('/admin/media-images/download?path=announcements%2Fpicnic.jpg', false)
            ->assertSee('title="Copy URL"', false)
            ->assertSee('title="Edit details"', false)
            ->assertSee('title="Replace"', false)
            ->assertSee('title="Delete"', false)
            ->assertDontSee('>Open<', false)
            ->assertDontSee('>Download<', false)
            ->assertDontSee('>Copy URL<', false)
            ->assertDontSee('>Edit details<', false)
            ->assertDontSee('>Replace image<', false)
            ->assertDontSee('>Delete image<', false)
            ->assertDontSee('>Upload new<', false)
            ->assertDontSee('bulletin.pdf');
    }

    public function test_image_download_route_forces_attachment_response(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('picnic.jpg', 1200, 630)
            ->storeAs('announcements', 'picnic.jpg', 'public');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.media-images.download', ['path' => 'announcements/picnic.jpg']))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=picnic.jpg');
    }

    public function test_media_library_builds_existing_image_picker_options(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('students.png', 800, 600)
            ->storeAs('ministries/content-images', 'students.png', 'public');

        $options = MediaLibrary::imageOptions();

        $this->assertArrayHasKey('ministries/content-images', $options);
        $this->assertArrayHasKey('ministries/content-images/students.png', $options['ministries/content-images']);
        $this->assertStringContainsString('students.png', $options['ministries/content-images']['ministries/content-images/students.png']);
        $this->assertStringContainsString('/storage/ministries/content-images/students.png', $options['ministries/content-images']['ministries/content-images/students.png']);
        $this->assertStringContainsString('Unused', $options['ministries/content-images']['ministries/content-images/students.png']);
    }

    public function test_media_library_can_save_image_title_slug_and_tags(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('students.png', 800, 600)
            ->storeAs('pages/content-images', 'students.png', 'public');

        Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->callAction('editImageMetadata', [
                'title' => 'Student Ministry Hero',
                'slug' => 'resources/student hero',
                'tags' => ['Students', 'Hero Image', 'students'],
            ], [
                'path' => 'pages/content-images/students.png',
            ])
            ->assertHasNoActionErrors();

        $metadata = MediaImageMetadata::query()->firstWhere('path', 'pages/content-images/students.png');

        $this->assertNotNull($metadata);
        $this->assertSame('Student Ministry Hero', $metadata->title);
        $this->assertSame('resources/student-hero', $metadata->slug);
        $this->assertSame(['Students', 'Hero Image'], $metadata->tags);

        $image = MediaLibrary::images()->firstWhere('path', 'pages/content-images/students.png');

        $this->assertSame('Student Ministry Hero', $image['display_title']);
        $this->assertSame('resources/student-hero', $image['slug']);
        $this->assertSame(['Students', 'Hero Image'], $image['tags']);

        $this->actingAs(User::factory()->create())
            ->get('/admin/media-library')
            ->assertOk()
            ->assertSee('Student Ministry Hero')
            ->assertSee('/resources/student-hero')
            ->assertSee('Students')
            ->assertSee('Hero Image');
    }

    public function test_media_library_tag_options_only_include_tags_from_existing_images(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('students.png', 800, 600)
            ->storeAs('pages/content-images', 'students.png', 'public');

        MediaImageMetadata::query()->create([
            'path' => 'pages/content-images/students.png',
            'tags' => ['Students', 'Hero'],
        ]);

        MediaImageMetadata::query()->create([
            'path' => 'missing/deleted.png',
            'tags' => ['Deleted'],
        ]);

        $this->assertSame([
            'Hero' => 'Hero',
            'Students' => 'Students',
        ], MediaLibrary::tagOptions());
    }

    public function test_media_library_tracks_content_block_image_usage(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('students.png', 800, 600)
            ->storeAs('pages/content-images', 'students.png', 'public');

        $page = Page::query()->create([
            'title' => 'Students',
            'slug' => 'students',
            'content_blocks' => [
                [
                    'type' => 'image_text',
                    'data' => [
                        'image_path' => 'pages/content-images/students.png',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $image = MediaLibrary::images()->firstWhere('path', 'pages/content-images/students.png');

        $this->assertNotNull($image);
        $this->assertSame(1, $image['usage_count']);
        $this->assertSame('Page: Students', $image['usage'][0]['label']);
        $this->assertSame('Content image', $image['usage'][0]['detail']);
        $this->assertSame(PageResource::getUrl('edit', ['record' => $page]), $image['usage'][0]['edit_url']);
        $this->assertSame('Pg: Students - Content image', $image['usage_summary']);

        $this->actingAs(User::factory()->create())
            ->get('/admin/media-library')
            ->assertOk()
            ->assertSee('href="'.PageResource::getUrl('edit', ['record' => $page]).'"', false)
            ->assertSee('Pg: Students | Content image', false);
    }

    public function test_media_library_can_delete_unused_image(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('unused.jpg', 800, 600)
            ->storeAs('announcements', 'unused.jpg', 'public');

        Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->callAction('deleteImage', arguments: ['path' => 'announcements/unused.jpg'])
            ->assertHasNoActionErrors();

        Storage::disk('public')->assertMissing('announcements/unused.jpg');
    }

    public function test_media_library_delete_removes_image_metadata(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('unused.jpg', 800, 600)
            ->storeAs('announcements', 'unused.jpg', 'public');

        MediaImageMetadata::query()->create([
            'path' => 'announcements/unused.jpg',
            'title' => 'Unused image',
            'tags' => ['Temporary'],
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->callAction('deleteImage', arguments: ['path' => 'announcements/unused.jpg'])
            ->assertHasNoActionErrors();

        Storage::disk('public')->assertMissing('announcements/unused.jpg');
        $this->assertDatabaseMissing(MediaImageMetadata::class, [
            'path' => 'announcements/unused.jpg',
        ]);
        $this->assertSame([], MediaLibrary::tagOptions());
    }

    public function test_media_library_replaces_image_everywhere_it_is_tracked(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('old.jpg', 800, 600)
            ->storeAs('announcements', 'old.jpg', 'public');

        $announcement = Announcement::query()->create([
            'title' => 'Church Picnic',
            'slug' => 'church-picnic',
            'image_path' => 'announcements/old.jpg',
            'content_blocks' => [
                [
                    'type' => 'image_text',
                    'data' => [
                        'image_path' => 'announcements/old.jpg',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->callAction('replaceImage', [
                'replacement_image' => UploadedFile::fake()->image('new.jpg', 800, 600),
                'title' => 'Updated Hero',
                'slug' => 'resources/updated hero',
                'tags' => ['Hero', 'Updated Hero'],
            ], [
                'path' => 'announcements/old.jpg',
            ])
            ->assertHasNoActionErrors();

        $announcement->refresh();
        $this->assertNotSame('announcements/old.jpg', $announcement->image_path);
        $this->assertStringStartsWith('media-library/', $announcement->image_path);
        $this->assertSame($announcement->image_path, $announcement->content_blocks[0]['data']['image_path']);
        Storage::disk('public')->assertMissing('announcements/old.jpg');
        Storage::disk('public')->assertExists($announcement->image_path);
    }

    public function test_media_library_replaces_image_metadata_path(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('old.jpg', 800, 600)
            ->storeAs('announcements', 'old.jpg', 'public');

        MediaImageMetadata::query()->create([
            'path' => 'announcements/old.jpg',
            'title' => 'Old Hero',
            'slug' => 'old-hero',
            'tags' => ['Hero'],
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->callAction('replaceImage', [
                'replacement_image' => UploadedFile::fake()->image('new.jpg', 800, 600),
                'title' => 'Updated Hero',
                'slug' => 'resources/updated hero',
                'tags' => ['Hero', 'Updated Hero'],
            ], [
                'path' => 'announcements/old.jpg',
            ])
            ->assertHasNoActionErrors();

        $metadata = MediaImageMetadata::query()->first();

        $this->assertNotNull($metadata);
        $this->assertStringStartsWith('media-library/', $metadata->path);
        $this->assertSame('Updated Hero', $metadata->title);
        $this->assertSame('resources/updated-hero', $metadata->slug);
        $this->assertSame(['Hero', 'Updated Hero'], $metadata->tags);
        $this->assertDatabaseMissing(MediaImageMetadata::class, [
            'path' => 'announcements/old.jpg',
        ]);
    }

    public function test_media_library_can_upload_new_images_from_header_action(): void
    {
        Storage::fake('public');

        Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->callAction('uploadImages', [
                'title' => 'New Upload',
                'slug' => 'gallery/new upload',
                'tags' => ['Gallery', 'Feature'],
                'image' => UploadedFile::fake()->image('new-upload.jpg', 800, 600),
            ])
            ->assertHasNoActionErrors();

        $path = Storage::disk('public')->files('media-library')[0] ?? null;

        $this->assertNotNull($path);
        $this->assertDatabaseHas(MediaImageMetadata::class, [
            'path' => $path,
            'title' => 'New Upload',
            'slug' => 'gallery/new-upload',
        ]);
        $this->assertSame(['Gallery', 'Feature'], MediaImageMetadata::query()->firstWhere('path', $path)->tags);
    }

    public function test_media_library_can_search_filename_path_and_usage(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('picnic.jpg', 800, 600)
            ->storeAs('announcements', 'picnic.jpg', 'public');

        UploadedFile::fake()
            ->image('students.png', 800, 600)
            ->storeAs('pages/content-images', 'students.png', 'public');

        Page::query()->create([
            'title' => 'Student Ministry',
            'slug' => 'students',
            'content_blocks' => [
                [
                    'type' => 'image_text',
                    'data' => [
                        'image_path' => 'pages/content-images/students.png',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        MediaImageMetadata::query()->create([
            'path' => 'pages/content-images/students.png',
            'title' => 'Student Hero',
            'slug' => 'student-ministry/hero',
            'tags' => ['Students'],
        ]);

        $component = Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->set('search', 'Student Ministry');

        $images = $component->instance()->getImages();

        $this->assertCount(1, $images);
        $this->assertSame('pages/content-images/students.png', $images->first()['path']);

        $component->set('search', 'announcements');

        $images = $component->instance()->getImages();

        $this->assertCount(1, $images);
        $this->assertSame('announcements/picnic.jpg', $images->first()['path']);

        $component->set('search', 'student-ministry/hero');

        $images = $component->instance()->getImages();

        $this->assertCount(1, $images);
        $this->assertSame('pages/content-images/students.png', $images->first()['path']);

        $component->set('search', 'Students');

        $images = $component->instance()->getImages();

        $this->assertCount(1, $images);
        $this->assertSame('pages/content-images/students.png', $images->first()['path']);
    }

    public function test_media_library_can_sort_images(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('zulu.jpg', 400, 300)
            ->size(100)
            ->storeAs('z-folder', 'zulu.jpg', 'public');

        UploadedFile::fake()
            ->image('alpha.jpg', 1200, 800)
            ->size(500)
            ->storeAs('a-folder', 'alpha.jpg', 'public');

        Announcement::query()->create([
            'title' => 'Alpha Announcement',
            'slug' => 'alpha-announcement',
            'image_path' => 'a-folder/alpha.jpg',
            'is_published' => true,
        ]);

        $component = Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class);

        $component->set('sort', 'file_name');
        $this->assertSame(['a-folder/alpha.jpg', 'z-folder/zulu.jpg'], $component->instance()->getImages()->pluck('path')->all());

        $component->set('sort', 'path');
        $this->assertSame(['a-folder/alpha.jpg', 'z-folder/zulu.jpg'], $component->instance()->getImages()->pluck('path')->all());

        $component->set('sort', 'size');
        $this->assertSame('a-folder/alpha.jpg', $component->instance()->getImages()->first()['path']);

        $component->set('sort', 'dimensions');
        $this->assertSame('a-folder/alpha.jpg', $component->instance()->getImages()->first()['path']);

        $component->set('sort', 'content_type');
        $this->assertSame('a-folder/alpha.jpg', $component->instance()->getImages()->first()['path']);
    }
}

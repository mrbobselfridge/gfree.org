<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Page;
use App\Models\User;
use App\Support\MediaLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee('Uploaded images')
            ->assertSee('picnic.jpg')
            ->assertSee('announcements/picnic.jpg')
            ->assertSee('Announcement: Church Picnic | Announcement image', false)
            ->assertSee('An: Church Picnic | Announceme..')
            ->assertSee('unused.jpg')
            ->assertSee('Unused')
            ->assertDontSee('bulletin.pdf');
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

    public function test_media_library_tracks_content_block_image_usage(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('students.png', 800, 600)
            ->storeAs('pages/content-images', 'students.png', 'public');

        Page::query()->create([
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
        $this->assertSame('Pg: Students - Content image', $image['usage_summary']);
    }
}

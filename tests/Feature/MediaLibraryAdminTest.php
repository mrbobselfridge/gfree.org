<?php

namespace Tests\Feature;

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
            ->create('bulletin.pdf', 50, 'application/pdf')
            ->storeAs('bulletins/pdfs', 'bulletin.pdf', 'public');

        $this->actingAs(User::factory()->create())
            ->get('/admin/media-library')
            ->assertOk()
            ->assertSee('Uploaded images')
            ->assertSee('picnic.jpg')
            ->assertSee('announcements/picnic.jpg')
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
    }
}

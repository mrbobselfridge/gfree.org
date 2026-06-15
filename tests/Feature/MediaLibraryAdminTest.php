<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\MediaLibrary as MediaLibraryPage;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Filament\Admin\Resources\Pages\Pages\CreatePage;
use App\Models\MediaImageMetadata;
use App\Models\Page;
use App\Models\User;
use App\Support\MediaLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
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
            ->storeAs('pages/header-images', 'picnic.jpg', 'public');

        UploadedFile::fake()
            ->image('unused.jpg', 1200, 630)
            ->storeAs('pages/header-images', 'unused.jpg', 'public');

        Page::query()->create([
            'title' => 'Church Picnic',
            'slug' => 'church-picnic',
            'hero_image_path' => 'pages/header-images/picnic.jpg',
            'is_published' => true,
        ]);

        UploadedFile::fake()
            ->create('document.pdf', 50, 'application/pdf')
            ->storeAs('file-library/documents', 'document.pdf', 'public');

        $this->actingAs(User::factory()->create())
            ->get('/admin/media-library')
            ->assertOk()
            ->assertSee('Images')
            ->assertSee('Uploaded images')
            ->assertSee('picnic.jpg')
            ->assertSee('pages/header-images/picnic.jpg')
            ->assertSee('Page: Church Picnic | Header image', false)
            ->assertSee('unused.jpg')
            ->assertSee('Unused')
            ->assertSee("mountAction('uploadImages')", false)
            ->assertDontSee("mountAction('createFile')", false)
            ->assertSee('title="Add"', false)
            ->assertSee('wire:partial="action-modals"', false)
            ->assertSee('title="Open"', false)
            ->assertSee('title="Download"', false)
            ->assertSee('/admin/media-images/download?path=pages%2Fheader-images%2Fpicnic.jpg', false)
            ->assertSee('title="Copy URL"', false)
            ->assertSee('title="Edit image"', false)
            ->assertDontSee('title="Edit details"', false)
            ->assertDontSee('title="Replace"', false)
            ->assertSee('title="Delete"', false)
            ->assertDontSee('>Open<', false)
            ->assertDontSee('>Download<', false)
            ->assertDontSee('>Copy URL<', false)
            ->assertDontSee('>Edit image<', false)
            ->assertDontSee('>Edit details<', false)
            ->assertDontSee('>Replace image<', false)
            ->assertDontSee('>Delete image<', false)
            ->assertDontSee('>Upload new<', false)
            ->assertDontSee('document.pdf');
    }

    public function test_image_download_route_forces_attachment_response(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('picnic.jpg', 1200, 630)
            ->storeAs('pages/header-images', 'picnic.jpg', 'public');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.media-images.download', ['path' => 'pages/header-images/picnic.jpg']))
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
        $user = User::factory()->create(['name' => 'Media Creator']);

        UploadedFile::fake()
            ->image('students.png', 800, 600)
            ->storeAs('pages/content-images', 'students.png', 'public');

        Livewire::actingAs($user)
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
        $this->assertSame(['Students', 'Hero Image', 'person', 'youth'], $metadata->tags);
        $this->assertSame($user->id, $metadata->created_by_user_id);

        $image = MediaLibrary::images()->firstWhere('path', 'pages/content-images/students.png');

        $this->assertSame('Student Ministry Hero', $image['display_title']);
        $this->assertSame('resources/student-hero', $image['slug']);
        $this->assertSame(['Students', 'Hero Image', 'person', 'youth'], $image['tags']);

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
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('Pg: Students | Content image', false);
    }

    public function test_media_library_can_delete_unused_image(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('unused.jpg', 800, 600)
            ->storeAs('pages/header-images', 'unused.jpg', 'public');

        Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->callAction('deleteImage', arguments: ['path' => 'pages/header-images/unused.jpg'])
            ->assertHasNoActionErrors();

        Storage::disk('public')->assertMissing('pages/header-images/unused.jpg');
    }

    public function test_media_library_delete_removes_image_metadata(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('unused.jpg', 800, 600)
            ->storeAs('pages/header-images', 'unused.jpg', 'public');

        MediaImageMetadata::query()->create([
            'path' => 'pages/header-images/unused.jpg',
            'title' => 'Unused image',
            'tags' => ['Temporary'],
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->callAction('deleteImage', arguments: ['path' => 'pages/header-images/unused.jpg'])
            ->assertHasNoActionErrors();

        Storage::disk('public')->assertMissing('pages/header-images/unused.jpg');
        $this->assertDatabaseMissing(MediaImageMetadata::class, [
            'path' => 'pages/header-images/unused.jpg',
        ]);
        $this->assertSame([], MediaLibrary::tagOptions());
    }

    public function test_media_library_replaces_image_everywhere_it_is_tracked(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('old.jpg', 800, 600)
            ->storeAs('pages/header-images', 'old.jpg', 'public');

        $page = Page::query()->create([
            'title' => 'Church Picnic',
            'slug' => 'church-picnic',
            'hero_image_path' => 'pages/header-images/old.jpg',
            'content_blocks' => [
                [
                    'type' => 'image_text',
                    'data' => [
                        'image_path' => 'pages/header-images/old.jpg',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->callAction('editImageMetadata', [
                'replacement_image' => UploadedFile::fake()->image('new.jpg', 800, 600),
                'title' => 'Updated Hero',
                'slug' => 'resources/updated hero',
                'tags' => ['Hero', 'Updated Hero'],
            ], [
                'path' => 'pages/header-images/old.jpg',
            ])
            ->assertHasNoActionErrors();

        $page->refresh();
        $this->assertNotSame('pages/header-images/old.jpg', $page->hero_image_path);
        $this->assertNewMediaLibraryImagePath($page->hero_image_path, 'new');
        $this->assertSame($page->hero_image_path, $page->content_blocks[0]['data']['image_path']);
        Storage::disk('public')->assertMissing('pages/header-images/old.jpg');
        Storage::disk('public')->assertExists($page->hero_image_path);
    }

    public function test_media_library_replaces_image_metadata_path(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('old.jpg', 800, 600)
            ->storeAs('pages/header-images', 'old.jpg', 'public');

        MediaImageMetadata::query()->create([
            'path' => 'pages/header-images/old.jpg',
            'title' => 'Old Hero',
            'slug' => 'old-hero',
            'tags' => ['Hero'],
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->callAction('editImageMetadata', [
                'replacement_image' => UploadedFile::fake()->image('new.jpg', 800, 600),
                'title' => 'Updated Hero',
                'slug' => 'resources/updated hero',
                'tags' => ['Hero', 'Updated Hero'],
            ], [
                'path' => 'pages/header-images/old.jpg',
            ])
            ->assertHasNoActionErrors();

        $metadata = MediaImageMetadata::query()->first();

        $this->assertNotNull($metadata);
        $this->assertNewMediaLibraryImagePath($metadata->path, 'new');
        $this->assertSame('Updated Hero', $metadata->title);
        $this->assertSame('resources/updated-hero', $metadata->slug);
        $this->assertSame(['Hero', 'Updated Hero'], $metadata->tags);
        $this->assertDatabaseMissing(MediaImageMetadata::class, [
            'path' => 'pages/header-images/old.jpg',
        ]);
    }

    public function test_media_library_defaults_replacement_title_from_uploaded_filename_when_title_is_unchanged(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('old.jpg', 800, 600)
            ->storeAs('pages/header-images', 'old.jpg', 'public');

        MediaImageMetadata::query()->create([
            'path' => 'pages/header-images/old.jpg',
            'title' => 'Old Hero',
            'slug' => 'old-hero',
            'tags' => ['Hero'],
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->callAction('editImageMetadata', [
                'replacement_image' => UploadedFile::fake()->image('updated_hero-photo.JPG', 800, 600),
            ], [
                'path' => 'pages/header-images/old.jpg',
            ])
            ->assertHasNoActionErrors();

        $metadata = MediaImageMetadata::query()->first();

        $this->assertNotNull($metadata);
        $this->assertNewMediaLibraryImagePath($metadata->path, 'updated-hero-photo');
        $this->assertSame('Updated Hero Photo', $metadata->title);
        $this->assertSame('updated-hero-photo', $metadata->slug);
        $this->assertSame(['Hero', 'picture'], $metadata->tags);
    }

    public function test_media_library_can_upload_new_images_from_header_action(): void
    {
        Storage::fake('public');
        $this->travelTo(Carbon::parse('2026-06-14 10:30:00'));
        $user = User::factory()->create([
            'name' => 'Noel Meyers',
            'email' => 'noel@example.test',
        ]);

        Livewire::actingAs($user)
            ->test(MediaLibraryPage::class)
            ->callAction('uploadImages', [
                'title' => 'New Upload',
                'slug' => 'gallery/new upload',
                'tags' => ['Gallery', 'Feature'],
                'image' => UploadedFile::fake()->image('new-upload.jpg', 800, 600),
            ])
            ->assertHasNoActionErrors();

        $path = Storage::disk('public')->allFiles('media-library')[0] ?? null;

        $this->assertNotNull($path);
        $this->assertNewMediaLibraryImagePath($path, 'new-upload');
        $this->assertDatabaseHas(MediaImageMetadata::class, [
            'path' => $path,
            'title' => 'New Upload',
            'slug' => 'gallery/new-upload',
        ]);
        $metadata = MediaImageMetadata::query()->firstWhere('path', $path);

        $this->assertSame(['Gallery', 'Feature'], $metadata->tags);
        $this->assertSame($user->id, $metadata->created_by_user_id);

        $image = MediaLibrary::images()->firstWhere('path', $path);

        $this->assertSame('Jun 14, 2026 10:30 AM', $image['created_at_for_humans']);
        $this->assertSame('Jun 14, 2026 10:30 AM', $image['updated_at_for_humans']);
        $this->assertSame('Noel Meyers', $image['created_by_name']);
        $this->assertSame('noel@example.test', $image['created_by_email']);

        $this->actingAs($user)
            ->get('/admin/media-library')
            ->assertOk()
            ->assertSee('Created: Jun 14, 2026 10:30 AM')
            ->assertSee('Updated: Jun 14, 2026 10:30 AM')
            ->assertSee('By: Noel Meyers');
    }

    public function test_page_image_upload_creates_clean_media_metadata_immediately(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreatePage::class)
            ->assertSee('Image details')
            ->assertFormFieldHidden('hero_image_path_media_title')
            ->assertFormFieldHidden('hero_image_path_media_tags')
            ->assertFormFieldHidden('hero_image_path_media_slug')
            ->set('data.title', 'Student Ministry')
            ->set('data.slug', 'student-ministry')
            ->set('data.hero_image_path', UploadedFile::fake()->image('student_ministry-hero.JPG', 800, 600))
            ->assertFormFieldVisible('hero_image_path_media_title')
            ->assertFormFieldVisible('hero_image_path_media_tags')
            ->assertFormFieldVisible('hero_image_path_media_slug')
            ->assertSet('data.hero_image_path_media_title', 'Student Ministry Hero')
            ->assertSet('data.hero_image_path_media_slug', 'student-ministry-hero')
            ->assertSet('data.hero_image_path_media_tags', ['person', 'youth'])
            ->set('data.hero_image_path_media_title', 'Custom Student Ministry Hero')
            ->set('data.hero_image_path_media_slug', 'custom/student ministry hero')
            ->set('data.hero_image_path_media_tags', ['Manual'])
            ->call('create')
            ->assertHasNoErrors();

        $path = Storage::disk('public')->allFiles('pages/hero-images')[0] ?? null;

        $this->assertNotNull($path);
        $this->assertMatchesRegularExpression(
            '#^pages/hero-images/[0-9a-hjkmnp-tv-z]{26}/student-ministry-hero\.(jpg|jpeg|png|gif|webp|avif|svg)$#',
            $path,
        );

        $metadata = MediaImageMetadata::query()->firstWhere('path', $path);

        $this->assertNotNull($metadata);
        $this->assertSame('Custom Student Ministry Hero', $metadata->title);
        $this->assertSame('custom/student-ministry-hero', $metadata->slug);
        $this->assertSame(['Manual', 'person', 'youth'], $metadata->tags);
        $this->assertSame($user->id, $metadata->created_by_user_id);
    }

    public function test_media_library_defaults_upload_title_from_uploaded_filename(): void
    {
        Storage::fake('public');

        Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->callAction('uploadImages', [
                'tags' => ['Students'],
                'image' => UploadedFile::fake()->image('student_ministry-hero.JPG', 800, 600),
                'image_original_name' => 'student_ministry-hero.JPG',
            ])
            ->assertHasNoActionErrors();

        $path = Storage::disk('public')->allFiles('media-library')[0] ?? null;

        $this->assertNotNull($path);
        $this->assertNewMediaLibraryImagePath($path, 'student-ministry-hero');
        $this->assertDatabaseHas(MediaImageMetadata::class, [
            'path' => $path,
            'title' => 'Student Ministry Hero',
            'slug' => 'student-ministry-hero',
        ]);
        $this->assertSame(['Students', 'person', 'youth'], MediaImageMetadata::query()->firstWhere('path', $path)->tags);
    }

    public function test_media_library_updates_upload_title_and_slug_as_soon_as_image_uploads(): void
    {
        Storage::fake('public');

        Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->mountAction('uploadImages')
            ->assertFormFieldVisible('image')
            ->assertFormFieldHidden('title')
            ->assertFormFieldHidden('tags')
            ->assertFormFieldHidden('slug')
            ->setActionData([
                'image' => UploadedFile::fake()->image('your-work-matters-church-website-banner.jpg', 1920, 650),
            ])
            ->assertFormFieldVisible('title')
            ->assertFormFieldVisible('tags')
            ->assertFormFieldVisible('slug')
            ->assertActionDataSet([
                'title' => 'Your Work Matters Church Website Banner',
                'slug' => 'your-work-matters-church-website-banner',
                'tags' => ['banner'],
            ]);
    }

    public function test_media_library_updates_replacement_title_and_slug_as_soon_as_image_uploads_when_existing_values_are_unchanged(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('old.jpg', 800, 600)
            ->storeAs('pages/header-images', 'old.jpg', 'public');

        MediaImageMetadata::query()->create([
            'path' => 'pages/header-images/old.jpg',
            'title' => 'Old Hero',
            'slug' => 'old-hero',
            'tags' => ['Hero'],
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->mountAction('editImageMetadata', [
                'path' => 'pages/header-images/old.jpg',
            ])
            ->assertFormFieldVisible('title')
            ->assertFormFieldVisible('tags')
            ->assertFormFieldVisible('slug')
            ->assertActionDataSet([
                'current_image' => 'pages/header-images/old.jpg',
                'title' => 'Old Hero',
                'slug' => 'old-hero',
            ])
            ->setActionData([
                'replacement_image' => UploadedFile::fake()->image('updated_hero-photo.JPG', 800, 600),
            ])
            ->assertActionDataSet([
                'title' => 'Updated Hero Photo',
                'slug' => 'updated-hero-photo',
                'tags' => ['Hero', 'picture'],
            ]);
    }

    public function test_media_library_adds_auto_tags_when_title_is_manually_changed(): void
    {
        Storage::fake('public');

        Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->mountAction('uploadImages')
            ->setActionData([
                'title' => 'Night of Worship Graphic',
            ])
            ->assertActionDataSet([
                'title' => 'Night of Worship Graphic',
                'tags' => ['graphic', 'worship'],
            ]);
    }

    public function test_media_library_makes_filename_default_slug_unique(): void
    {
        Storage::fake('public');

        UploadedFile::fake()
            ->image('existing.jpg', 800, 600)
            ->storeAs('media-library', 'existing.jpg', 'public');

        MediaImageMetadata::query()->create([
            'path' => 'media-library/existing.jpg',
            'title' => 'Existing',
            'slug' => 'student-ministry-hero',
            'tags' => [],
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->callAction('uploadImages', [
                'image' => UploadedFile::fake()->image('student_ministry-hero.JPG', 800, 600),
                'image_original_name' => 'student_ministry-hero.JPG',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas(MediaImageMetadata::class, [
            'title' => 'Student Ministry Hero',
            'slug' => 'student-ministry-hero-2',
        ]);
    }

    public function test_media_library_auto_tags_known_title_keywords(): void
    {
        $cases = [
            'Church Website Banner' => ['banner'],
            'Ministry Graphic Background' => ['graphic'],
            'Unsplash Photo Image' => ['picture'],
            'Square Logo Icon' => ['logo'],
            'Pastor Family Volunteer Hands' => ['person'],
            'Sunday School VBS Kids' => ['person', 'kids & children', 'event or service'],
            'Unchained Night' => ['youth'],
            'Night of Worship Music' => ['worship'],
            'Good Friday Easter Spring' => ['holiday and seasonal'],
            'Giving Tithe Offering' => ['giving and offering'],
            'Prayer and Fasting' => ['prayer'],
            'Baptism Service Picnic' => ['event or service'],
        ];

        foreach ($cases as $title => $expectedTags) {
            $this->assertSame($expectedTags, MediaImageMetadata::autoTagsForTitle($title));
        }
    }

    public function test_media_library_auto_tags_are_added_without_removing_manual_tags(): void
    {
        Storage::fake('public');

        Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->callAction('uploadImages', [
                'tags' => ['Custom'],
                'image' => UploadedFile::fake()->image('your-work-matters-church-website-banner.jpg', 1920, 650),
            ])
            ->assertHasNoActionErrors();

        $metadata = MediaImageMetadata::query()->first();

        $this->assertNotNull($metadata);
        $this->assertNewMediaLibraryImagePath($metadata->path, 'your-work-matters-church-website-banner');
        $this->assertSame('Your Work Matters Church Website Banner', $metadata->title);
        $this->assertSame('your-work-matters-church-website-banner', $metadata->slug);
        $this->assertSame(['Custom', 'banner'], $metadata->tags);
    }

    public function test_media_library_can_search_filename_path_and_usage(): void
    {
        Storage::fake('public');
        $this->travelTo(Carbon::parse('2026-06-14 09:15:00'));
        $creator = User::factory()->create([
            'name' => 'Avery Media Admin',
            'email' => 'avery.media@example.test',
        ]);

        UploadedFile::fake()
            ->image('picnic.jpg', 800, 600)
            ->storeAs('uploads', 'picnic.jpg', 'public');

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
            'created_by_user_id' => $creator->id,
        ]);

        $component = Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->set('search', 'Student Ministry');

        $images = $component->instance()->getImages();

        $this->assertCount(1, $images);
        $this->assertSame('pages/content-images/students.png', $images->first()['path']);

        $component->set('search', 'uploads');

        $images = $component->instance()->getImages();

        $this->assertCount(1, $images);
        $this->assertSame('uploads/picnic.jpg', $images->first()['path']);

        $component->set('search', 'student-ministry/hero');

        $images = $component->instance()->getImages();

        $this->assertCount(1, $images);
        $this->assertSame('pages/content-images/students.png', $images->first()['path']);

        $component->set('search', 'Students');

        $images = $component->instance()->getImages();

        $this->assertCount(1, $images);
        $this->assertSame('pages/content-images/students.png', $images->first()['path']);

        $component->set('search', 'Avery Media Admin');

        $images = $component->instance()->getImages();

        $this->assertCount(1, $images);
        $this->assertSame('pages/content-images/students.png', $images->first()['path']);

        $component->set('search', 'avery.media@example.test');

        $images = $component->instance()->getImages();

        $this->assertCount(1, $images);
        $this->assertSame('pages/content-images/students.png', $images->first()['path']);

        $component->set('search', 'Jun 14, 2026');

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

        Page::query()->create([
            'title' => 'Alpha Page',
            'slug' => 'alpha-announcement',
            'hero_image_path' => 'a-folder/alpha.jpg',
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

    public function test_media_library_loads_images_in_batches(): void
    {
        Storage::fake('public');

        foreach (range(1, 30) as $number) {
            UploadedFile::fake()
                ->image(sprintf('image-%02d.jpg', $number), 400, 300)
                ->storeAs('bulk', sprintf('image-%02d.jpg', $number), 'public');
        }

        $component = Livewire::actingAs(User::factory()->create())
            ->test(MediaLibraryPage::class)
            ->set('sort', 'file_name');

        $this->assertCount(24, $component->instance()->getImages());
        $this->assertSame(30, $component->instance()->getTotalImageCount());
        $this->assertSame(30, $component->instance()->getFilteredImageCount());
        $this->assertTrue($component->instance()->hasMoreImages());

        $component
            ->assertSee('24 of 30 images shown')
            ->assertSee('Load more')
            ->call('loadMoreImages')
            ->assertHasNoErrors();

        $this->assertCount(30, $component->instance()->getImages());
        $this->assertFalse($component->instance()->hasMoreImages());

        $component->set('search', 'image-29');

        $this->assertSame(24, $component->instance()->imageLimit);
        $this->assertCount(1, $component->instance()->getImages());
        $this->assertSame('bulk/image-29.jpg', $component->instance()->getImages()->first()['path']);
        $this->assertSame(1, $component->instance()->getFilteredImageCount());
    }

    public function test_media_library_paged_image_query_slices_filtered_results(): void
    {
        Storage::fake('public');

        foreach (range(1, 5) as $number) {
            UploadedFile::fake()
                ->image(sprintf('resource-%02d.jpg', $number), 400, 300)
                ->storeAs('resources', sprintf('resource-%02d.jpg', $number), 'public');
        }

        MediaImageMetadata::query()->create([
            'path' => 'resources/resource-05.jpg',
            'title' => 'Youth Resource Graphic',
            'slug' => 'youth/resource-graphic',
            'tags' => ['Students'],
        ]);

        $results = MediaLibrary::pagedImages(
            search: 'students',
            sort: 'file_name',
            limit: 1,
        );

        $this->assertSame(5, $results['total']);
        $this->assertSame(1, $results['filtered_total']);
        $this->assertFalse($results['has_more']);
        $this->assertSame('resources/resource-05.jpg', $results['items']->first()['path']);

        $results = MediaLibrary::pagedImages(
            sort: 'file_name',
            limit: 2,
            offset: 2,
        );

        $this->assertSame(5, $results['total']);
        $this->assertSame(5, $results['filtered_total']);
        $this->assertTrue($results['has_more']);
        $this->assertSame(['resources/resource-03.jpg', 'resources/resource-04.jpg'], $results['items']->pluck('path')->all());
    }

    public function test_existing_image_picker_action_mounts_with_paged_defaults(): void
    {
        Storage::fake('public');

        foreach (range(1, 30) as $number) {
            UploadedFile::fake()
                ->image(sprintf('picker-%02d.jpg', $number), 400, 300)
                ->storeAs('picker', sprintf('picker-%02d.jpg', $number), 'public');
        }

        Livewire::actingAs(User::factory()->create())
            ->test(CreatePage::class)
            ->assertFormComponentActionExists('hero_image_path', 'chooseExistingImage')
            ->mountFormComponentAction('hero_image_path', 'chooseExistingImage')
            ->assertFormComponentActionMounted('hero_image_path', 'chooseExistingImage');

        $results = MediaLibrary::pagedImages(limit: 24);

        $this->assertCount(24, $results['items']);
        $this->assertSame(30, $results['total']);
        $this->assertTrue($results['has_more']);
    }

    private function assertNewMediaLibraryImagePath(string $path, string $name): void
    {
        $this->assertMatchesRegularExpression(
            "#^media-library/[0-9a-hjkmnp-tv-z]{26}/{$name}\.(jpg|jpeg|png|gif|webp|avif|svg)$#",
            $path,
        );
    }
}

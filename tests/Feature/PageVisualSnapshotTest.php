<?php

namespace Tests\Feature;

use App\Models\Bulletin;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\PageVisualSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Tests\TestCase;

class PageVisualSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_preview_route_renders_unpublished_page_content(): void
    {
        $this->withoutVite();

        SiteSetting::query()->create([
            'church_name' => 'Grace Free Church',
        ]);

        $page = Page::query()->create([
            'title' => 'Draft Connect',
            'slug' => 'draft-connect',
            'intro' => 'This draft should still render.',
            'is_published' => false,
            'show_site_chrome' => true,
            'show_page_header' => true,
            'content_blocks' => [
                [
                    'type' => 'text',
                    'data' => [
                        'heading' => 'Draft block heading',
                        'body' => '<p>Draft block body.</p>',
                    ],
                ],
            ],
        ]);

        $this->get(app(PageVisualSnapshot::class)->previewUrl($page))
            ->assertOk()
            ->assertSee('Draft Connect')
            ->assertSee('This draft should still render.')
            ->assertSee('Draft block heading')
            ->assertSee('Draft block body.');
    }

    public function test_preview_route_requires_valid_signature(): void
    {
        $this->get(route('admin.page-visual-snapshots.preview', [
            'type' => 'page',
            'record' => 1,
        ]))->assertForbidden();
    }

    public function test_signed_image_route_serves_private_snapshot_png(): void
    {
        Storage::disk('local')->put('page-visual-snapshots/test.png', 'png-bytes');

        $url = URL::temporarySignedRoute(
            'admin.page-visual-snapshots.image',
            now()->addMinute(),
            ['path' => 'page-visual-snapshots/test.png'],
        );

        $response = $this->get($url);

        $response
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->assertSame('png-bytes', file_get_contents($response->baseResponse->getFile()->getPathname()));
    }

    public function test_image_route_requires_valid_signature(): void
    {
        Storage::disk('local')->put('page-visual-snapshots/test.png', 'png-bytes');

        $this->get(route('admin.page-visual-snapshots.image', [
            'path' => 'page-visual-snapshots/test.png',
        ]))->assertForbidden();
    }

    public function test_image_route_rejects_paths_outside_snapshot_folder(): void
    {
        Storage::disk('local')->put('other/test.png', 'png-bytes');

        $url = URL::temporarySignedRoute(
            'admin.page-visual-snapshots.image',
            now()->addMinute(),
            ['path' => 'other/test.png'],
        );

        $this->get($url)->assertNotFound();
    }

    public function test_bulletins_are_not_supported_for_visual_snapshots(): void
    {
        $bulletin = Bulletin::query()->create([
            'title' => 'Sunday Bulletin',
            'bulletin_date' => '2026-06-14',
            'is_published' => true,
        ]);

        $snapshot = app(PageVisualSnapshot::class);

        $this->assertFalse($snapshot->supports($bulletin));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not available for visual page snapshots');

        $snapshot->previewUrl($bulletin);
    }
}

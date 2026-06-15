<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\PageVisualSnapshot;
use App\Support\PageVisualSnapshotResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Mockery;
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

    public function test_debug_snapshot_command_captures_named_page(): void
    {
        $page = Page::query()->create([
            'title' => 'Debug Snapshot Page',
            'slug' => 'debug-snapshot-page',
            'is_published' => true,
        ]);

        $snapshot = new PageVisualSnapshotResult(
            path: 'page-visual-snapshots/debug.png',
            absolutePath: Storage::disk('local')->path('page-visual-snapshots/debug.png'),
            previewUrl: 'https://example.test/preview',
            width: 1440,
            height: 1000,
            imageUrl: 'https://example.test/image',
        );

        $visualSnapshot = Mockery::mock(PageVisualSnapshot::class);
        $visualSnapshot
            ->shouldReceive('previewUrl')
            ->once()
            ->with(Mockery::on(fn (Page $capturedPage): bool => $capturedPage->is($page)))
            ->andReturn($snapshot->previewUrl);
        $visualSnapshot
            ->shouldReceive('capture')
            ->once()
            ->with(Mockery::on(fn (Page $capturedPage): bool => $capturedPage->is($page)))
            ->andReturn($snapshot);

        $this->app->instance(PageVisualSnapshot::class, $visualSnapshot);

        $status = Artisan::call('debug:page-visual-snapshot', [
            'slug' => 'debug-snapshot-page',
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $status);
        $this->assertStringContainsString('Page: #'.$page->getKey().' Debug Snapshot Page', $output);
        $this->assertStringContainsString('Preview URL: https://example.test/preview', $output);
        $this->assertStringContainsString('Snapshot captured.', $output);
        $this->assertStringContainsString('Path: page-visual-snapshots/debug.png', $output);
        $this->assertStringContainsString('Image URL: https://example.test/image', $output);
    }

    public function test_debug_snapshot_command_reports_missing_page(): void
    {
        $status = Artisan::call('debug:page-visual-snapshot', [
            'slug' => 'missing-page',
        ]);

        $this->assertSame(1, $status);
        $this->assertStringContainsString('No page found for slug [missing-page].', Artisan::output());
    }
}

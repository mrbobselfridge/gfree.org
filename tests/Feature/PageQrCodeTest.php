<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\PageQrCode;
use App\Models\User;
use App\Support\MediaLibrary;
use App\Support\PageQrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PageQrCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_page_creates_png_and_svg_qr_code_files(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-06-17 12:00:00');

        $page = Page::query()->create([
            'title' => 'Visit',
            'slug' => 'visit',
            'is_published' => true,
        ]);

        $qrCode = $page->qrCode()->firstOrFail();

        $this->assertSame(url('/visit'), $qrCode->url);
        $this->assertSame(PageQrCodeService::pngPath($page), $qrCode->png_path);
        $this->assertSame(PageQrCodeService::svgPath($page), $qrCode->svg_path);
        $this->assertSame('2026-06-17 12:00:00', $qrCode->generated_at?->format('Y-m-d H:i:s'));

        Storage::disk('public')->assertExists($qrCode->png_path);
        Storage::disk('public')->assertExists($qrCode->svg_path);
        $this->assertStringStartsWith("\x89PNG", Storage::disk('public')->get($qrCode->png_path));
        $this->assertStringContainsString('<svg', Storage::disk('public')->get($qrCode->svg_path));
    }

    public function test_updating_a_page_regenerates_the_same_qr_record_for_the_new_url(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-06-17 12:00:00');

        $page = Page::query()->create([
            'title' => 'Visit',
            'slug' => 'visit',
            'is_published' => true,
        ]);

        $qrCode = $page->qrCode()->firstOrFail();

        Carbon::setTestNow('2026-06-17 13:00:00');

        $page->update([
            'slug' => 'plan-a-visit',
        ]);

        $qrCode->refresh();

        $this->assertSame(1, PageQrCode::query()->where('page_id', $page->getKey())->count());
        $this->assertSame(url('/plan-a-visit'), $qrCode->url);
        $this->assertSame(PageQrCodeService::pngPath($page), $qrCode->png_path);
        $this->assertSame(PageQrCodeService::svgPath($page), $qrCode->svg_path);
        $this->assertSame('2026-06-17 13:00:00', $qrCode->generated_at?->format('Y-m-d H:i:s'));
        Storage::disk('public')->assertExists($qrCode->png_path);
        Storage::disk('public')->assertExists($qrCode->svg_path);
    }

    public function test_repeated_page_saves_do_not_create_duplicate_qr_records(): void
    {
        Storage::fake('public');

        $page = Page::query()->create([
            'title' => 'Visit',
            'slug' => 'visit',
            'is_published' => true,
        ]);

        $page->update(['title' => 'Visit Us']);
        $page->update(['title' => 'Plan a Visit']);

        $this->assertSame(1, PageQrCode::query()->where('page_id', $page->getKey())->count());
    }

    public function test_deleting_a_page_deletes_qr_metadata_and_files(): void
    {
        Storage::fake('public');

        $page = Page::query()->create([
            'title' => 'Visit',
            'slug' => 'visit',
            'is_published' => true,
        ]);

        $qrCode = $page->qrCode()->firstOrFail();

        $page->delete();

        $this->assertDatabaseCount(PageQrCode::class, 0);
        Storage::disk('public')->assertMissing($qrCode->png_path);
        Storage::disk('public')->assertMissing($qrCode->svg_path);
    }

    public function test_edit_page_shows_the_qr_code_panel_and_download_links(): void
    {
        Storage::fake('public');

        $page = Page::query()->create([
            'title' => 'Visit',
            'slug' => 'visit',
            'is_published' => true,
        ]);

        $qrCode = $page->qrCode()->firstOrFail();

        Livewire::actingAs(User::factory()->create())
            ->test(EditPage::class, ['record' => $page->getKey()])
            ->assertSee('QR Code')
            ->assertSee(url('/visit'))
            ->assertSee('Download PNG')
            ->assertSee('Download SVG')
            ->assertSee(Storage::disk('public')->url($qrCode->png_path), false)
            ->assertSee(Storage::disk('public')->url($qrCode->svg_path), false);
    }

    public function test_page_qr_code_assets_do_not_appear_in_the_media_library(): void
    {
        Storage::fake('public');

        Page::query()->create([
            'title' => 'Visit',
            'slug' => 'visit',
            'is_published' => true,
        ]);

        $this->assertSame([], MediaLibrary::images()
            ->pluck('path')
            ->filter(fn (string $path): bool => str_starts_with($path, 'page-qr-codes/'))
            ->values()
            ->all());
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\SlideDecks\SlideDeckResource;
use App\Filament\Admin\Resources\SlideDecks\Pages\CreateSlideDeck;
use App\Jobs\ProcessSlideDeckJob;
use App\Models\FileDocument;
use App\Models\SlideDeck;
use App\Models\SlideDeckSlide;
use App\Models\User;
use App\Support\NullSlideAnalyzer;
use App\Support\PowerPointToPdfService;
use App\Support\SlideAnalysisService;
use App\Support\SlideDeckImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class SlideDeckImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_slide_deck_import_and_original_file_library_record(): void
    {
        Storage::fake(SlideDeck::DISK);
        Queue::fake();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        Livewire::actingAs($admin)
            ->test(CreateSlideDeck::class)
            ->set('data.name', 'Weekly Announcements')
            ->set('data.deck_upload', UploadedFile::fake()->create(
                'weekly-announcements.pptx',
                25,
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ))
            ->set('data.original_filename', 'weekly-announcements.pptx')
            ->call('create')
            ->assertHasNoErrors();

        $deck = SlideDeck::query()->firstOrFail();

        $this->assertSame('Weekly Announcements', $deck->name);
        $this->assertSame(SlideDeck::STATUS_PENDING, $deck->status);
        $this->assertSame('weekly-announcements.pptx', $deck->original_filename);
        $this->assertStringStartsWith("slide-decks/{$deck->getKey()}/original/", $deck->stored_file_path);
        Storage::disk(SlideDeck::DISK)->assertExists($deck->stored_file_path);

        $document = FileDocument::query()->firstOrFail();

        $this->assertSame($document->getKey(), $deck->file_document_id);
        $this->assertSame('Weekly Announcements', $document->title);
        $this->assertSame('Slide Deck', $document->category);
        $this->assertSame(FileDocument::VISIBILITY_PRIVATE, $document->visibility);
        $this->assertSame('weekly-announcements.pptx', $document->currentVersion->original_name);

        Queue::assertPushed(ProcessSlideDeckJob::class);
    }

    public function test_slide_deck_image_zip_and_metadata_exports_are_available(): void
    {
        Storage::fake(SlideDeck::DISK);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $deck = SlideDeck::query()->create([
            'name' => 'Announcements',
            'original_filename' => 'announcements.pptx',
            'stored_file_path' => 'slide-decks/1/original/announcements.pptx',
            'status' => SlideDeck::STATUS_COMPLETED,
            'total_slides' => 1,
            'processed_slides' => 1,
        ]);
        $slide = $deck->slides()->create([
            'slide_number' => 1,
            'image_path' => $deck->directory('images').'/slide-001.png',
            'thumbnail_path' => $deck->directory('thumbnails').'/slide-001.png',
            'slide_type' => SlideDeckSlide::TYPE_ANNOUNCEMENT,
            'suggested_name' => 'Family Fire Night',
            'event_title' => 'Family Fire Night',
        ]);

        Storage::disk(SlideDeck::DISK)->put($slide->image_path, 'png image');
        Storage::disk(SlideDeck::DISK)->put($slide->thumbnail_path, 'png thumbnail');

        $this->actingAs($admin)
            ->get(route('admin.slide-decks.image', ['slideDeckSlide' => $slide]))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $jsonResponse = $this->actingAs($admin)
            ->get(route('admin.slide-decks.export', ['slideDeck' => $deck, 'format' => 'json']));

        $jsonResponse
            ->assertOk()
            ->assertHeader('content-type', 'application/json');
        $this->assertStringContainsString('Family Fire Night', $jsonResponse->getContent());

        $csvResponse = $this->actingAs($admin)
            ->get(route('admin.slide-decks.export', ['slideDeck' => $deck, 'format' => 'csv']));

        $csvResponse
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Family Fire Night', $csvResponse->streamedContent());

        $this->actingAs($admin)
            ->get(route('admin.slide-decks.download-images', ['slideDeck' => $deck]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=announcements-slides.zip');
    }

    public function test_admin_can_open_slide_deck_review_page(): void
    {
        Storage::fake(SlideDeck::DISK);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $deck = SlideDeck::query()->create([
            'name' => 'Announcements',
            'original_filename' => 'announcements.pptx',
            'stored_file_path' => 'slide-decks/1/original/announcements.pptx',
            'status' => SlideDeck::STATUS_COMPLETED,
            'total_slides' => 1,
            'processed_slides' => 1,
        ]);
        $slide = $deck->slides()->create([
            'slide_number' => 1,
            'image_path' => $deck->directory('images').'/slide-001.png',
            'thumbnail_path' => $deck->directory('thumbnails').'/slide-001.png',
            'slide_type' => SlideDeckSlide::TYPE_GENERAL,
            'suggested_name' => 'Offering Reminder',
        ]);

        Storage::disk(SlideDeck::DISK)->put($slide->image_path, 'png image');
        Storage::disk(SlideDeck::DISK)->put($slide->thumbnail_path, 'png thumbnail');

        $this->actingAs($admin)
            ->get(SlideDeckResource::getUrl('edit', ['record' => $deck]))
            ->assertOk()
            ->assertSee('Edit Announcements');
    }

    public function test_null_slide_analyzer_provides_fallback_slide_metadata(): void
    {
        $deck = SlideDeck::query()->create([
            'name' => 'Announcements',
            'original_filename' => 'announcements.pptx',
            'stored_file_path' => 'slide-decks/1/original/announcements.pptx',
        ]);
        $slide = $deck->slides()->create([
            'slide_number' => 3,
            'image_path' => 'slide-decks/1/images/slide-003.png',
        ]);

        (new SlideAnalysisService(new NullSlideAnalyzer()))->analyze($slide);

        $slide->refresh();

        $this->assertSame(SlideDeckSlide::TYPE_UNKNOWN, $slide->slide_type);
        $this->assertSame('Slide 3', $slide->suggested_name);
        $this->assertSame('No AI slide analyzer is configured yet.', $slide->summary);
        $this->assertSame('0.0000', $slide->confidence_score);
    }

    public function test_processing_job_marks_deck_failed_when_conversion_fails(): void
    {
        $deck = SlideDeck::query()->create([
            'name' => 'Announcements',
            'original_filename' => 'announcements.pptx',
            'stored_file_path' => 'slide-decks/1/original/announcements.pptx',
        ]);

        $this->app->instance(PowerPointToPdfService::class, new class extends PowerPointToPdfService {
            public function convert(SlideDeck $deck, string $workDirectory): string
            {
                throw new RuntimeException('LibreOffice is missing.');
            }
        });

        (new ProcessSlideDeckJob($deck))->handle($this->app->make(SlideDeckImportService::class));

        $deck->refresh();

        $this->assertSame(SlideDeck::STATUS_FAILED, $deck->status);
        $this->assertSame('LibreOffice is missing.', $deck->error_message);
    }
}

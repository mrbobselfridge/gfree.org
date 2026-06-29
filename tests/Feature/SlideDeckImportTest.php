<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\SlideDecks\Pages\CreateSlideDeck;
use App\Filament\Admin\Resources\SlideDecks\Pages\EditSlideDeck;
use App\Filament\Admin\Resources\SlideDecks\RelationManagers\SlidesRelationManager;
use App\Filament\Admin\Resources\SlideDecks\SlideDeckResource;
use App\Jobs\ProcessSlideDeckJob;
use App\Models\FileDocument;
use App\Models\SiteSetting;
use App\Models\SlideDeck;
use App\Models\SlideDeckSlide;
use App\Models\User;
use App\Support\NullSlideAnalyzer;
use App\Support\OpenAiSlideAnalyzer;
use App\Support\PowerPointToPdfService;
use App\Support\SlideAnalysisService;
use App\Support\SlideAnalyzerInterface;
use App\Support\SlideDeckImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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
        $this->assertStringContainsString('intro_text', $jsonResponse->getContent());
        $this->assertStringNotContainsString('summary', $jsonResponse->getContent());

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

        (new SlideAnalysisService(new NullSlideAnalyzer))->analyze($slide);

        $slide->refresh();

        $this->assertSame(SlideDeckSlide::TYPE_UNKNOWN, $slide->slide_type);
        $this->assertSame('Slide 3', $slide->suggested_name);
        $this->assertSame('No AI slide analyzer is configured yet.', $slide->summary);
        $this->assertSame('0.0000', $slide->confidence_score);
    }

    public function test_slide_analyzer_binding_uses_null_analyzer_without_site_settings_key(): void
    {
        $this->assertInstanceOf(NullSlideAnalyzer::class, app(SlideAnalyzerInterface::class));
    }

    public function test_openai_slide_analyzer_sends_slide_image_and_saves_structured_metadata(): void
    {
        Storage::fake(SlideDeck::DISK);
        Storage::disk(SlideDeck::DISK)->put('slide-decks/1/images/slide-001.png', 'fake-png-bytes');

        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'openai_api_key' => 'test-key',
        ]);

        config([
            'services.openai.content_model' => 'gpt-5-mini',
        ]);

        $deck = SlideDeck::query()->create([
            'name' => 'Announcements',
            'original_filename' => 'announcements.pptx',
            'stored_file_path' => 'slide-decks/1/original/announcements.pptx',
        ]);
        $slide = $deck->slides()->create([
            'slide_number' => 1,
            'image_path' => 'slide-decks/1/images/slide-001.png',
        ]);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output_text' => json_encode([
                    'slide_type' => 'announcement',
                    'suggested_name' => 'Family Fire Night - July 19',
                    'extracted_text' => 'Family Fire Night July 19',
                    'intro_text' => 'Bring the family for dinner, games, and connection at Family Fire Night on July 19.',
                    'event_title' => 'Family Fire Night',
                    'event_date' => 'July 19',
                    'event_time' => '6:00 PM',
                    'event_location' => 'Fellowship Hall',
                    'event_audience' => 'Families',
                    'contact_person' => 'Sam',
                    'announcement_details' => 'Dinner and games.',
                    'confidence_score' => 0.92,
                ]),
            ]),
        ]);

        $this->assertInstanceOf(OpenAiSlideAnalyzer::class, app(SlideAnalyzerInterface::class));

        app(SlideAnalysisService::class)->analyze($slide);

        $slide->refresh();

        $this->assertSame(SlideDeckSlide::TYPE_ANNOUNCEMENT, $slide->slide_type);
        $this->assertSame('Family Fire Night - July 19', $slide->suggested_name);
        $this->assertSame('Family Fire Night July 19', $slide->extracted_text);
        $this->assertSame('Bring the family for dinner, games, and connection at Family Fire Night on July 19.', $slide->summary);
        $this->assertSame('Family Fire Night', $slide->event_title);
        $this->assertSame('July 19', $slide->event_date);
        $this->assertSame('6:00 PM', $slide->event_time);
        $this->assertSame('Fellowship Hall', $slide->event_location);
        $this->assertSame('Families', $slide->event_audience);
        $this->assertSame('Sam', $slide->contact_person);
        $this->assertSame('Dinner and games.', $slide->announcement_details);
        $this->assertSame('0.9200', $slide->confidence_score);

        Http::assertSent(function (Request $request): bool {
            $content = data_get($request->data(), 'input.0.content');
            $prompt = (string) data_get($content, '0.text');

            return $request->url() === 'https://api.openai.com/v1/responses'
                && data_get($request->data(), 'model') === 'gpt-5-mini'
                && str_contains($prompt, 'Classify the slide into exactly one slide_type')
                && str_contains($prompt, 'intro_text must be no more than 300 total characters')
                && str_contains($prompt, 'Return only valid JSON')
                && data_get($content, '1.type') === 'input_image'
                && data_get($content, '1.detail') === 'high'
                && data_get($content, '1.image_url') === 'data:image/png;base64,'.base64_encode('fake-png-bytes');
        });
    }

    public function test_openai_quota_errors_are_saved_as_user_facing_slide_analysis_failures(): void
    {
        Storage::fake(SlideDeck::DISK);
        Storage::disk(SlideDeck::DISK)->put('slide-decks/1/images/slide-001.png', 'fake-png-bytes');

        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'openai_api_key' => 'test-key',
        ]);

        $deck = SlideDeck::query()->create([
            'name' => 'Announcements',
            'original_filename' => 'announcements.pptx',
            'stored_file_path' => 'slide-decks/1/original/announcements.pptx',
        ]);
        $slide = $deck->slides()->create([
            'slide_number' => 1,
            'image_path' => 'slide-decks/1/images/slide-001.png',
            'suggested_name' => 'Slide 1',
        ]);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'error' => [
                    'message' => 'You exceeded your current quota, please check your plan and billing details.',
                    'type' => 'insufficient_quota',
                    'code' => 'insufficient_quota',
                ],
            ], 429),
        ]);

        app(SlideAnalysisService::class)->analyze($slide);

        $slide->refresh();

        $this->assertSame('openai_quota_exceeded', data_get($slide->raw_analysis_json, 'error_type'));
        $this->assertTrue(data_get($slide->raw_analysis_json, 'analyzer_failed'));
        $this->assertStringContainsString('OpenAI API balance or quota issue', data_get($slide->raw_analysis_json, 'error'));
    }

    public function test_slide_review_table_shows_openai_balance_issue(): void
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
        ]);
        $slide = $deck->slides()->create([
            'slide_number' => 1,
            'image_path' => $deck->directory('images').'/slide-001.png',
            'thumbnail_path' => $deck->directory('thumbnails').'/slide-001.png',
            'slide_type' => SlideDeckSlide::TYPE_UNKNOWN,
            'suggested_name' => 'Slide 1',
            'raw_analysis_json' => [
                'error' => 'OpenAI API balance or quota issue: add API credits or raise the project or organization limit, then re-run analysis.',
                'error_type' => 'openai_quota_exceeded',
                'analyzer_failed' => true,
            ],
        ]);

        Storage::disk(SlideDeck::DISK)->put($slide->image_path, 'png image');
        Storage::disk(SlideDeck::DISK)->put($slide->thumbnail_path, 'png thumbnail');

        Livewire::actingAs($admin)
            ->test(SlidesRelationManager::class, [
                'ownerRecord' => $deck,
                'pageClass' => EditSlideDeck::class,
            ])
            ->assertTableColumnExists('analysis_status')
            ->assertSee('OpenAI balance issue')
            ->assertSee('add API credits or raise the project or organization limit');
    }

    public function test_processing_job_marks_deck_failed_when_conversion_fails(): void
    {
        $deck = SlideDeck::query()->create([
            'name' => 'Announcements',
            'original_filename' => 'announcements.pptx',
            'stored_file_path' => 'slide-decks/1/original/announcements.pptx',
        ]);

        $this->app->instance(PowerPointToPdfService::class, new class extends PowerPointToPdfService
        {
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

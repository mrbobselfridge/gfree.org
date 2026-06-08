<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Bulletins\Pages\EditBulletin;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Filament\Admin\Resources\Pages\Pages\EditPage;
use App\Filament\Admin\Support\AiPageReviewActions;
use App\Models\Bulletin;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\OpenAiPageReviewer;
use App\Support\PageReviewSnapshot;
use App\Support\PageVisualSnapshot;
use App\Support\PageVisualSnapshotResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class OpenAiPageReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_review_sends_admin_snapshot_and_site_prompt(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'openai_api_key' => 'test-key',
            'openai_bulletin_model' => 'gpt-5-mini',
            'ai_content_prompt' => 'Review for first-time visitors.',
        ]);

        config([
            'services.openai.content_model' => 'gpt-5-mini',
        ]);

        $page = Page::query()->create([
            'title' => 'Plan a Visit',
            'slug' => 'plan-a-visit',
            'intro' => 'Draft visitor intro.',
            'is_published' => false,
            'content_blocks' => [
                [
                    'type' => 'text',
                    'data' => [
                        'heading' => 'Come Sunday',
                        'body' => '<p>Service starts at 10.</p>',
                    ],
                ],
            ],
        ]);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output_text' => "Overall assessment\nGood start.",
            ]),
        ]);

        $snapshot = app(PageReviewSnapshot::class)->forRecord($page);
        $review = app(OpenAiPageReviewer::class)->review($snapshot, SiteSetting::query()->value('ai_content_prompt'));

        $this->assertSame("Overall assessment\nGood start.", $review);

        Http::assertSent(function (Request $request) use ($page): bool {
            $text = (string) data_get($request->data(), 'input.0.content.0.text');

            return $request->url() === 'https://api.openai.com/v1/responses'
                && data_get($request->data(), 'model') === 'gpt-5-mini'
                && str_contains($text, 'Review for first-time visitors.')
                && str_contains($text, '"page_type": "Page"')
                && str_contains($text, '"slug": "'.$page->slug.'"')
                && str_contains($text, '"is_published": false')
                && str_contains($text, 'Ignore site navigation and footer')
                && str_contains($text, 'Suggested field updates');
        });
    }

    public function test_page_review_sends_visual_snapshot_when_available(): void
    {
        Storage::disk('local')->put('page-visual-snapshots/test.png', 'fake-png-bytes');

        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'openai_api_key' => 'test-key',
            'openai_bulletin_model' => 'gpt-5-mini',
        ]);

        config([
            'services.openai.content_model' => 'gpt-5-mini',
        ]);

        $page = Page::query()->create([
            'title' => 'Plan a Visit',
            'slug' => 'plan-a-visit',
            'is_published' => true,
        ]);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output_text' => "Overall assessment\nThe screenshot is useful.",
            ]),
        ]);

        $snapshot = app(PageReviewSnapshot::class)->forRecord($page);
        $visualSnapshot = new PageVisualSnapshotResult(
            path: 'page-visual-snapshots/test.png',
            absolutePath: Storage::disk('local')->path('page-visual-snapshots/test.png'),
            previewUrl: 'https://example.test/preview',
            width: 1440,
            height: 1000,
        );

        app(OpenAiPageReviewer::class)->review($snapshot, 'Review layout too.', $visualSnapshot);

        Http::assertSent(function (Request $request): bool {
            $content = data_get($request->data(), 'input.0.content');
            $prompt = (string) data_get($content, '0.text');

            return data_get($content, '0.type') === 'input_text'
                && str_contains($prompt, 'A desktop full-page screenshot is attached')
                && data_get($content, '1.type') === 'input_image'
                && data_get($content, '1.detail') === 'auto'
                && data_get($content, '1.image_url') === 'data:image/png;base64,'.base64_encode('fake-png-bytes');
        });
    }

    public function test_page_review_action_captures_visual_snapshot_for_openai(): void
    {
        Storage::disk('local')->put('page-visual-snapshots/action-test.png', 'action-png-bytes');

        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'openai_api_key' => 'test-key',
            'openai_bulletin_model' => 'gpt-5-mini',
            'ai_content_prompt' => 'Review the whole page.',
        ]);

        config([
            'services.openai.content_model' => 'gpt-5-mini',
        ]);

        $page = Page::query()->create([
            'title' => 'Connect',
            'slug' => 'connect',
            'intro' => 'Find your next step.',
            'is_published' => false,
        ]);

        $visualSnapshot = new PageVisualSnapshotResult(
            path: 'page-visual-snapshots/action-test.png',
            absolutePath: Storage::disk('local')->path('page-visual-snapshots/action-test.png'),
            previewUrl: 'https://example.test/preview',
            width: 1440,
            height: 1000,
            imageUrl: 'https://example.test/snapshot-image',
        );

        $visualSnapshotService = Mockery::mock(PageVisualSnapshot::class);
        $visualSnapshotService
            ->shouldReceive('capture')
            ->once()
            ->with(Mockery::on(fn (Page $record): bool => $record->is($page)))
            ->andReturn($visualSnapshot);

        $this->app->instance(PageVisualSnapshot::class, $visualSnapshotService);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output_text' => "Overall assessment\nThe page review includes the screenshot.",
            ]),
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditPage::class, ['record' => $page->getKey()])
            ->callAction('aiPageReview', [
                'prompt' => 'Review visual layout.',
            ])
            ->assertSet('mountedActions.0.data.visual_snapshot_url', 'https://example.test/snapshot-image')
            ->assertHasNoActionErrors();

        Http::assertSent(function (Request $request): bool {
            $content = data_get($request->data(), 'input.0.content');

            return data_get($content, '1.type') === 'input_image'
                && data_get($content, '1.image_url') === 'data:image/png;base64,'.base64_encode('action-png-bytes');
        });
    }

    public function test_page_review_action_is_available_on_pages_but_not_bulletins(): void
    {
        $page = Page::query()->create([
            'title' => 'Visit',
            'slug' => 'visit',
            'is_published' => false,
        ]);

        $bulletin = Bulletin::query()->create([
            'title' => 'Sunday Bulletin',
            'bulletin_date' => '2026-06-14',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditPage::class, ['record' => $page->getKey()])
            ->assertActionExists('aiPageReview');

        Livewire::actingAs(User::factory()->create())
            ->test(EditBulletin::class, ['record' => $bulletin->getKey()])
            ->assertActionDoesNotExist('aiPageReview')
            ->assertActionExists('extractPdf')
            ->assertActionExists('reviewAnnouncements');
    }

    public function test_page_review_modal_icon_actions_render_valid_livewire_calls(): void
    {
        $actionsHtml = view('filament.admin.forms.components.ai-page-review-actions')->render();
        $emailHtml = view('filament.admin.forms.components.ai-page-review-email-actions', [
            'emailArguments' => ['email' => true],
        ])->render();
        $visualHtml = view('filament.admin.forms.components.ai-page-review-visual-snapshot', [
            'visualSnapshotUrl' => 'https://example.test/snapshot-image',
        ])->render();

        $this->assertStringContainsString("content: 'AI Review'", $actionsHtml);
        $this->assertStringContainsString('aria-label="AI Review"', $actionsHtml);
        $this->assertStringContainsString("content: 'Close'", $actionsHtml);
        $this->assertStringContainsString('aria-label="Close"', $actionsHtml);
        $this->assertStringContainsString('wire:click="callMountedAction"', $actionsHtml);
        $this->assertStringContainsString('wire:click="unmountAction"', $actionsHtml);

        $this->assertStringContainsString("content: 'Email Results'", $emailHtml);
        $this->assertStringContainsString('aria-label="Email Results"', $emailHtml);
        $this->assertStringContainsString('wire:click="callMountedAction(JSON.parse(', $emailHtml);
        $this->assertStringContainsString('\u0022email\u0022:true', $emailHtml);

        $this->assertStringContainsString('Page screenshot', $visualHtml);
        $this->assertStringContainsString('Open full-size screenshot', $visualHtml);
        $this->assertStringContainsString('src="https://example.test/snapshot-image"', $visualHtml);
    }

    public function test_page_review_email_uses_page_context_subject_and_body(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'Grace Free Church',
        ]);

        $user = User::factory()->create([
            'email' => 'editor@example.com',
        ]);

        $page = Page::query()->create([
            'title' => 'Connect',
            'slug' => 'connect',
            'is_published' => true,
        ]);

        $this->actingAs($user);

        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $body, callable $callback) use ($page): bool {
                $message = Mockery::mock(Message::class);
                $message
                    ->shouldReceive('to')
                    ->once()
                    ->with('editor@example.com')
                    ->andReturnSelf();
                $message
                    ->shouldReceive('subject')
                    ->once()
                    ->with('AI Review: '.$page->publicUrl())
                    ->andReturnSelf();

                $callback($message);

                return str_contains($body, 'Reviewed @ ')
                    && str_contains($body, ' for Grace Free Church')
                    && str_contains($body, 'Page Reviewed: Connect - '.$page->publicUrl())
                    && str_contains($body, 'Edit Content: '.PageResource::getUrl('edit', ['record' => $page]))
                    && str_contains($body, 'Overall assessment: Looks good.');
            });

        $method = new \ReflectionMethod(AiPageReviewActions::class, 'emailReview');
        $method->invoke(null, 'Overall assessment: Looks good.', $page);
    }
}

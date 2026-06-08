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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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

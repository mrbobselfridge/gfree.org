<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Bulletins\Pages\EditBulletin;
use App\Filament\Admin\Resources\Pages\Pages\CreatePage;
use App\Jobs\SendWorkflowNotificationJob;
use App\Mail\WorkflowNotificationMail;
use App\Models\Bulletin;
use App\Models\Page;
use App\Models\User;
use App\Models\WorkflowNotificationEvent;
use App\Models\WorkflowNotificationRule;
use App\Models\WorkflowVisualSnapshot;
use App\Support\AdminAccess;
use App\Support\PageVisualSnapshot;
use App\Support\PageVisualSnapshotResult;
use App\Support\WorkflowNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class WorkflowNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_workflow_notification_rules(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/workflow-notification-rules/create')
            ->assertOk()
            ->assertSee('Content area')
            ->assertSee('Manual')
            ->assertSee('All admins')
            ->assertSee('Extra email addresses')
            ->assertSee('Automatic send delay')
            ->assertSee('Email');
    }

    public function test_automatic_notifications_are_debounced_for_the_same_rule_and_record(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-06-05 09:00:00');

        $recipient = User::factory()->create([
            'email' => 'connection@example.com',
        ]);

        $rule = WorkflowNotificationRule::query()->create([
            'name' => 'Bulletin ready',
            'content_area' => AdminAccess::BULLETINS,
            'triggers' => [WorkflowNotificationRule::TRIGGER_UPDATED],
            'selected_user_ids' => [$recipient->getKey()],
            'subject' => 'Bulletin ready',
            'message' => 'Please update the connection card.',
            'delay_minutes' => 15,
            'is_enabled' => true,
        ]);

        $bulletin = Bulletin::query()->create([
            'title' => 'June 7 Bulletin',
            'bulletin_date' => '2026-06-07',
            'is_published' => true,
        ]);

        app(WorkflowNotificationService::class)->automaticForRecord($bulletin, WorkflowNotificationRule::TRIGGER_UPDATED);

        $event = WorkflowNotificationEvent::query()->firstOrFail();

        $this->assertSame($rule->getKey(), $event->workflow_notification_rule_id);
        $this->assertSame(WorkflowNotificationEvent::STATUS_PENDING, $event->status);
        $this->assertSame(['connection@example.com'], $event->recipient_emails);
        $this->assertSame('2026-06-05 09:15:00', $event->scheduled_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow('2026-06-05 09:10:00');

        app(WorkflowNotificationService::class)->automaticForRecord($bulletin, WorkflowNotificationRule::TRIGGER_UPDATED);

        $this->assertSame(1, WorkflowNotificationEvent::query()->count());
        $this->assertSame('2026-06-05 09:25:00', $event->refresh()->scheduled_at->format('Y-m-d H:i:s'));

        Queue::assertPushed(SendWorkflowNotificationJob::class, 2);

        Carbon::setTestNow();
    }

    public function test_manual_notification_cancels_matching_pending_automatic_notification_for_that_instance(): void
    {
        Queue::fake();
        Mail::fake();

        $recipient = User::factory()->create([
            'email' => 'announcements@example.com',
        ]);

        WorkflowNotificationRule::query()->create([
            'name' => 'Review bulletin',
            'content_area' => AdminAccess::BULLETINS,
            'triggers' => [
                WorkflowNotificationRule::TRIGGER_UPDATED,
                WorkflowNotificationRule::TRIGGER_MANUAL,
            ],
            'selected_user_ids' => [$recipient->getKey()],
            'subject' => 'Review bulletin',
            'message' => 'Please create matching announcements.',
            'delay_minutes' => 30,
            'is_enabled' => true,
        ]);

        $bulletin = Bulletin::query()->create([
            'title' => 'June 7 Bulletin',
            'bulletin_date' => '2026-06-07',
            'is_published' => true,
        ]);

        $service = app(WorkflowNotificationService::class);

        $service->automaticForRecord($bulletin, WorkflowNotificationRule::TRIGGER_UPDATED);

        $pending = WorkflowNotificationEvent::query()->firstOrFail();

        $sentCount = $service->manualForRecord($bulletin, [$pending->workflow_notification_rule_id]);

        $this->assertSame(1, $sentCount);
        $this->assertSame(WorkflowNotificationEvent::STATUS_CANCELLED, $pending->refresh()->status);
        $this->assertSame(WorkflowNotificationEvent::STATUS_SENT, WorkflowNotificationEvent::query()->latest('id')->first()->status);

        Mail::assertSent(WorkflowNotificationMail::class, fn (WorkflowNotificationMail $mail): bool => $mail->hasTo('announcements@example.com'));
    }

    public function test_manual_rule_shows_notify_team_action_on_matching_edit_screen(): void
    {
        WorkflowNotificationRule::query()->create([
            'name' => 'Review bulletin',
            'content_area' => AdminAccess::BULLETINS,
            'triggers' => [WorkflowNotificationRule::TRIGGER_MANUAL],
            'extra_emails' => 'announcements@example.com',
            'subject' => 'Review bulletin',
            'message' => 'Please create matching announcements.',
            'delay_minutes' => 15,
            'is_enabled' => true,
        ]);

        $bulletin = Bulletin::query()->create([
            'title' => 'June 7 Bulletin',
            'bulletin_date' => '2026-06-07',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditBulletin::class, ['record' => $bulletin->getKey()])
            ->assertActionExists('notifyTeam')
            ->assertActionHasLabel('notifyTeam', 'Notify');
    }

    public function test_create_record_hook_queues_matching_workflow_notification(): void
    {
        Queue::fake();
        $this->mockVisualSnapshots(['page-visual-snapshots/create-baseline.png']);

        $recipient = User::factory()->create([
            'email' => 'page-review@example.com',
        ]);

        WorkflowNotificationRule::query()->create([
            'name' => 'Page created',
            'content_area' => AdminAccess::PAGES,
            'triggers' => [WorkflowNotificationRule::TRIGGER_CREATED],
            'selected_user_ids' => [$recipient->getKey()],
            'subject' => 'Page created',
            'message' => 'Please review the new page.',
            'delay_minutes' => 15,
            'is_enabled' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(CreatePage::class)
            ->set('data.title', 'Workflow Page')
            ->set('data.slug', 'workflow-page')
            ->set('data.is_published', true)
            ->set('data.show_site_chrome', true)
            ->set('data.show_page_header', true)
            ->call('create')
            ->assertHasNoErrors();

        $page = Page::query()->where('slug', 'workflow-page')->firstOrFail();
        $event = WorkflowNotificationEvent::query()->firstOrFail();

        $this->assertSame(Page::class, $event->record_type);
        $this->assertSame($page->getKey(), $event->record_id);
        $this->assertSame(WorkflowNotificationRule::TRIGGER_CREATED, $event->trigger);
        $this->assertSame($page->publicUrl(), $event->public_url);
        $this->assertSame(['page-review@example.com'], $event->recipient_emails);
        $this->assertSame(
            'page-visual-snapshots/create-baseline.png',
            WorkflowVisualSnapshot::query()->whereMorphedTo('snapshotable', $page)->firstOrFail()->snapshot_path,
        );

        Queue::assertPushed(SendWorkflowNotificationJob::class);
    }

    public function test_automatic_update_notification_uses_visual_baseline_as_pre_and_advances_baseline_after_send(): void
    {
        Queue::fake();
        Mail::fake();
        $this->mockVisualSnapshots(['page-visual-snapshots/post-update.png']);

        $recipient = User::factory()->create([
            'email' => 'page-review@example.com',
        ]);

        WorkflowNotificationRule::query()->create([
            'name' => 'Page updated',
            'content_area' => AdminAccess::PAGES,
            'triggers' => [WorkflowNotificationRule::TRIGGER_UPDATED],
            'selected_user_ids' => [$recipient->getKey()],
            'subject' => 'Page updated',
            'message' => 'Please review the page update.',
            'delay_minutes' => 0,
            'is_enabled' => true,
        ]);

        $page = Page::query()->create([
            'title' => 'Workflow Page',
            'slug' => 'workflow-page',
            'is_published' => true,
            'show_site_chrome' => true,
            'show_page_header' => true,
        ]);

        WorkflowVisualSnapshot::query()->create([
            'snapshotable_type' => Page::class,
            'snapshotable_id' => $page->getKey(),
            'snapshot_path' => 'page-visual-snapshots/baseline.png',
            'snapshot_captured_at' => now()->subHour(),
        ]);

        $service = app(WorkflowNotificationService::class);
        $service->automaticForRecord($page, WorkflowNotificationRule::TRIGGER_UPDATED);

        $event = WorkflowNotificationEvent::query()->firstOrFail();

        $this->assertSame('page-visual-snapshots/baseline.png', $event->pre_snapshot_path);
        $this->assertNull($event->post_snapshot_path);

        $service->send($event->refresh());

        $event->refresh();

        $this->assertSame(WorkflowNotificationEvent::STATUS_SENT, $event->status);
        $this->assertSame('page-visual-snapshots/baseline.png', $event->pre_snapshot_path);
        $this->assertSame('page-visual-snapshots/post-update.png', $event->post_snapshot_path);
        $this->assertSame(
            'page-visual-snapshots/post-update.png',
            WorkflowVisualSnapshot::query()->whereMorphedTo('snapshotable', $page)->firstOrFail()->snapshot_path,
        );

        Mail::assertSent(WorkflowNotificationMail::class, function (WorkflowNotificationMail $mail): bool {
            $html = $mail->render();

            return $mail->hasTo('page-review@example.com')
                && str_contains($html, 'Visual comparison')
                && str_contains($html, 'PRE')
                && str_contains($html, 'POST')
                && str_contains($html, 'https://example.test/snapshots/page-visual-snapshots/baseline.png')
                && str_contains($html, 'https://example.test/snapshots/page-visual-snapshots/post-update.png')
                && substr_count($html, 'width="50%"') === 2
                && substr_count($html, 'max-width: 50%') === 2;
        });
    }

    public function test_manual_notification_uses_visual_baseline_as_pre_and_current_snapshot_as_post(): void
    {
        Mail::fake();
        $this->mockVisualSnapshots(['page-visual-snapshots/manual-post.png']);

        $recipient = User::factory()->create([
            'email' => 'page-review@example.com',
        ]);

        $rule = WorkflowNotificationRule::query()->create([
            'name' => 'Manual page review',
            'content_area' => AdminAccess::PAGES,
            'triggers' => [WorkflowNotificationRule::TRIGGER_MANUAL],
            'selected_user_ids' => [$recipient->getKey()],
            'subject' => 'Manual page review',
            'message' => 'Please review the page.',
            'delay_minutes' => 0,
            'is_enabled' => true,
        ]);

        $page = Page::query()->create([
            'title' => 'Workflow Page',
            'slug' => 'workflow-page',
            'is_published' => true,
            'show_site_chrome' => true,
            'show_page_header' => true,
        ]);

        WorkflowVisualSnapshot::query()->create([
            'snapshotable_type' => Page::class,
            'snapshotable_id' => $page->getKey(),
            'snapshot_path' => 'page-visual-snapshots/manual-pre.png',
            'snapshot_captured_at' => now()->subHour(),
        ]);

        $sentCount = app(WorkflowNotificationService::class)->manualForRecord($page, [$rule->getKey()]);

        $event = WorkflowNotificationEvent::query()->firstOrFail();

        $this->assertSame(1, $sentCount);
        $this->assertSame(WorkflowNotificationEvent::STATUS_SENT, $event->status);
        $this->assertSame('page-visual-snapshots/manual-pre.png', $event->pre_snapshot_path);
        $this->assertSame('page-visual-snapshots/manual-post.png', $event->post_snapshot_path);
        $this->assertSame(
            'page-visual-snapshots/manual-post.png',
            WorkflowVisualSnapshot::query()->whereMorphedTo('snapshotable', $page)->firstOrFail()->snapshot_path,
        );

        Mail::assertSent(WorkflowNotificationMail::class, fn (WorkflowNotificationMail $mail): bool => $mail->hasTo('page-review@example.com')
            && $mail->event->pre_snapshot_path === 'page-visual-snapshots/manual-pre.png'
            && $mail->event->post_snapshot_path === 'page-visual-snapshots/manual-post.png');
    }

    public function test_repeated_updates_keep_the_original_pre_visual_snapshot(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-06-05 09:00:00');

        $recipient = User::factory()->create([
            'email' => 'page-review@example.com',
        ]);

        WorkflowNotificationRule::query()->create([
            'name' => 'Page updated',
            'content_area' => AdminAccess::PAGES,
            'triggers' => [WorkflowNotificationRule::TRIGGER_UPDATED],
            'selected_user_ids' => [$recipient->getKey()],
            'subject' => 'Page updated',
            'message' => 'Please review the page update.',
            'delay_minutes' => 15,
            'is_enabled' => true,
        ]);

        $page = Page::query()->create([
            'title' => 'Workflow Page',
            'slug' => 'workflow-page',
            'is_published' => true,
            'show_site_chrome' => true,
            'show_page_header' => true,
        ]);

        WorkflowVisualSnapshot::query()->create([
            'snapshotable_type' => Page::class,
            'snapshotable_id' => $page->getKey(),
            'snapshot_path' => 'page-visual-snapshots/original-pre.png',
            'snapshot_captured_at' => now()->subDay(),
        ]);

        $service = app(WorkflowNotificationService::class);
        $service->automaticForRecord($page, WorkflowNotificationRule::TRIGGER_UPDATED);

        Carbon::setTestNow('2026-06-05 09:10:00');

        WorkflowVisualSnapshot::query()->whereMorphedTo('snapshotable', $page)->update([
            'snapshot_path' => 'page-visual-snapshots/should-not-replace-pre.png',
        ]);

        $service->automaticForRecord($page, WorkflowNotificationRule::TRIGGER_UPDATED);

        $event = WorkflowNotificationEvent::query()->firstOrFail();

        $this->assertSame(1, WorkflowNotificationEvent::query()->count());
        $this->assertSame('page-visual-snapshots/original-pre.png', $event->pre_snapshot_path);
        $this->assertSame('2026-06-05 09:25:00', $event->scheduled_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_workflow_notification_email_shows_pre_and_post_visual_snapshots(): void
    {
        $this->mockVisualSnapshots();

        $rule = WorkflowNotificationRule::query()->create([
            'name' => 'Page updated',
            'content_area' => AdminAccess::PAGES,
            'triggers' => [WorkflowNotificationRule::TRIGGER_UPDATED],
            'extra_emails' => 'page-review@example.com',
            'subject' => 'Page updated',
            'message' => 'Please review the page update.',
            'delay_minutes' => 15,
            'is_enabled' => true,
        ]);

        $event = WorkflowNotificationEvent::query()->create([
            'workflow_notification_rule_id' => $rule->getKey(),
            'content_area' => AdminAccess::PAGES,
            'trigger' => WorkflowNotificationRule::TRIGGER_UPDATED,
            'status' => WorkflowNotificationEvent::STATUS_PENDING,
            'record_key' => Page::class.':1',
            'pre_snapshot_path' => 'page-visual-snapshots/pre.png',
            'post_snapshot_path' => 'page-visual-snapshots/post.png',
            'scheduled_at' => now(),
            'recipient_emails' => ['page-review@example.com'],
        ]);

        $html = view('mail.workflow-notification', ['event' => $event])->render();

        $this->assertStringContainsString('Visual comparison', $html);
        $this->assertStringContainsString('PRE', $html);
        $this->assertStringContainsString('POST', $html);
        $this->assertStringContainsString('table-layout: fixed', $html);
        $this->assertSame(2, substr_count($html, 'width="50%"'));
        $this->assertSame(2, substr_count($html, 'style="width: 50%; max-width: 50%;'));
        $this->assertStringContainsString('width="100%" style="display: block; width: 100%; max-width: 100%;', $html);
        $this->assertStringNotContainsString('max-width: 320px', $html);
        $this->assertStringContainsString('https://example.test/snapshots/page-visual-snapshots/pre.png', $html);
        $this->assertStringContainsString('https://example.test/snapshots/page-visual-snapshots/post.png', $html);
    }

    public function test_create_workflow_notification_email_shows_only_post_visual_snapshot(): void
    {
        $this->mockVisualSnapshots();

        $rule = WorkflowNotificationRule::query()->create([
            'name' => 'Page created',
            'content_area' => AdminAccess::PAGES,
            'triggers' => [WorkflowNotificationRule::TRIGGER_CREATED],
            'extra_emails' => 'page-review@example.com',
            'subject' => 'Page created',
            'message' => 'Please review the new page.',
            'delay_minutes' => 15,
            'is_enabled' => true,
        ]);

        $event = WorkflowNotificationEvent::query()->create([
            'workflow_notification_rule_id' => $rule->getKey(),
            'content_area' => AdminAccess::PAGES,
            'trigger' => WorkflowNotificationRule::TRIGGER_CREATED,
            'status' => WorkflowNotificationEvent::STATUS_PENDING,
            'record_key' => Page::class.':1',
            'pre_snapshot_path' => 'page-visual-snapshots/pre.png',
            'post_snapshot_path' => 'page-visual-snapshots/post.png',
            'scheduled_at' => now(),
            'recipient_emails' => ['page-review@example.com'],
        ]);

        $html = view('mail.workflow-notification', ['event' => $event])->render();

        $this->assertStringContainsString('Visual snapshot', $html);
        $this->assertStringNotContainsString('Visual comparison', $html);
        $this->assertStringNotContainsString('PRE', $html);
        $this->assertStringNotContainsString('POST', $html);
        $this->assertStringContainsString('td width="100%" valign="top" style="width: 100%; max-width: 100%; padding: 0 0 12px 0;', $html);
        $this->assertStringContainsString('alt="Page screenshot"', $html);
        $this->assertStringNotContainsString('https://example.test/snapshots/page-visual-snapshots/pre.png', $html);
        $this->assertStringContainsString('https://example.test/snapshots/page-visual-snapshots/post.png', $html);
    }

    /**
     * @param  array<int, string>  $capturePaths
     */
    private function mockVisualSnapshots(array $capturePaths = []): void
    {
        $this->mock(PageVisualSnapshot::class, function ($mock) use ($capturePaths): void {
            $paths = $capturePaths;

            $mock->shouldReceive('supports')
                ->zeroOrMoreTimes()
                ->andReturnUsing(fn (mixed $record): bool => $record instanceof Page);

            $mock->shouldReceive('capture')
                ->zeroOrMoreTimes()
                ->andReturnUsing(function (Page $page) use (&$paths): PageVisualSnapshotResult {
                    $path = array_shift($paths) ?: 'page-visual-snapshots/mock.png';

                    return new PageVisualSnapshotResult(
                        path: $path,
                        absolutePath: storage_path('app/'.$path),
                        previewUrl: 'https://example.test/preview',
                        width: PageVisualSnapshot::DEFAULT_WIDTH,
                        height: PageVisualSnapshot::DEFAULT_HEIGHT,
                        imageUrl: 'https://example.test/snapshots/'.$path,
                    );
                });

            $mock->shouldReceive('imageUrl')
                ->zeroOrMoreTimes()
                ->andReturnUsing(fn (string $path): string => 'https://example.test/snapshots/'.$path);
        });
    }
}

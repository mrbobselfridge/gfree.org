<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Pages\Pages\CreatePage;
use App\Filament\Admin\Resources\Pages\Pages\EditPage;
use App\Jobs\SendWorkflowNotificationJob;
use App\Mail\WorkflowNotificationMail;
use App\Models\Page;
use App\Models\SiteSetting;
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
            'name' => 'Page ready',
            'content_area' => AdminAccess::PAGES,
            'triggers' => [WorkflowNotificationRule::TRIGGER_UPDATED],
            'selected_user_ids' => [$recipient->getKey()],
            'subject' => 'Page ready',
            'message' => 'Please update the connection card.',
            'delay_minutes' => 15,
            'is_enabled' => true,
        ]);

        $page = Page::query()->create([
            'title' => 'June 7 Page',
            'slug' => 'june-7-page',
            'is_published' => true,
        ]);

        app(WorkflowNotificationService::class)->automaticForRecord($page, WorkflowNotificationRule::TRIGGER_UPDATED);

        $event = WorkflowNotificationEvent::query()->firstOrFail();

        $this->assertSame($rule->getKey(), $event->workflow_notification_rule_id);
        $this->assertSame(WorkflowNotificationEvent::STATUS_PENDING, $event->status);
        $this->assertSame(['connection@example.com'], $event->recipient_emails);
        $this->assertSame('2026-06-05 09:15:00', $event->scheduled_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow('2026-06-05 09:10:00');

        app(WorkflowNotificationService::class)->automaticForRecord($page, WorkflowNotificationRule::TRIGGER_UPDATED);

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
            'name' => 'Review page',
            'content_area' => AdminAccess::PAGES,
            'triggers' => [
                WorkflowNotificationRule::TRIGGER_UPDATED,
                WorkflowNotificationRule::TRIGGER_MANUAL,
            ],
            'selected_user_ids' => [$recipient->getKey()],
            'subject' => 'Review page',
            'message' => 'Please review the page update.',
            'delay_minutes' => 30,
            'is_enabled' => true,
        ]);

        $page = Page::query()->create([
            'title' => 'June 7 Page',
            'slug' => 'june-7-page',
            'is_published' => true,
        ]);

        $service = app(WorkflowNotificationService::class);

        $service->automaticForRecord($page, WorkflowNotificationRule::TRIGGER_UPDATED);

        $pending = WorkflowNotificationEvent::query()->firstOrFail();

        $sentCount = $service->manualForRecord($page, [$pending->workflow_notification_rule_id]);

        $this->assertSame(1, $sentCount);
        $this->assertSame(WorkflowNotificationEvent::STATUS_CANCELLED, $pending->refresh()->status);
        $this->assertSame(WorkflowNotificationEvent::STATUS_SENT, WorkflowNotificationEvent::query()->latest('id')->first()->status);

        Mail::assertSent(WorkflowNotificationMail::class, fn (WorkflowNotificationMail $mail): bool => $mail->hasTo('announcements@example.com'));
    }

    public function test_manual_rule_shows_notify_team_action_on_matching_edit_screen(): void
    {
        WorkflowNotificationRule::query()->create([
            'name' => 'Review page',
            'content_area' => AdminAccess::PAGES,
            'triggers' => [WorkflowNotificationRule::TRIGGER_MANUAL],
            'extra_emails' => 'announcements@example.com',
            'subject' => 'Review page',
            'message' => 'Please review this page.',
            'delay_minutes' => 15,
            'is_enabled' => true,
        ]);

        $page = Page::query()->create([
            'title' => 'June 7 Page',
            'slug' => 'june-7-page',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditPage::class, ['record' => $page->getKey()])
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
                && str_contains($html, 'Before Changes')
                && str_contains($html, 'After Changes')
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
        $this->assertStringContainsString('Before Changes', $html);
        $this->assertStringContainsString('After Changes', $html);
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
        $this->assertStringContainsString('Saved Created Page', $html);
        $this->assertStringNotContainsString('Before Changes', $html);
        $this->assertStringNotContainsString('After Changes', $html);
        $this->assertStringContainsString('td width="100%" valign="top" style="width: 100%; max-width: 100%; padding: 0 0 12px 0;', $html);
        $this->assertStringContainsString('alt="Saved Created Page page screenshot"', $html);
        $this->assertStringNotContainsString('https://example.test/snapshots/page-visual-snapshots/pre.png', $html);
        $this->assertStringContainsString('https://example.test/snapshots/page-visual-snapshots/post.png', $html);
    }

    public function test_workflow_notification_subject_and_message_render_template_items(): void
    {
        Carbon::setTestNow('2026-06-15 14:35:00');
        $this->mockVisualSnapshots();

        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
        ]);

        $actor = User::factory()->create([
            'name' => 'Editor Person',
            'email' => 'editor@example.com',
        ]);

        $rule = WorkflowNotificationRule::query()->create([
            'name' => 'Page updated',
            'content_area' => AdminAccess::PAGES,
            'triggers' => [WorkflowNotificationRule::TRIGGER_UPDATED],
            'extra_emails' => 'page-review@example.com',
            'subject' => '{church_name}: {page_title} {action_status} by {updater_name}',
            'message' => 'Site {site_name} saw {page_title} get {action_status} on {current_date} at {current_time}. Contact {updater_email}. Full: {current_datetime}.',
            'delay_minutes' => 15,
            'is_enabled' => true,
        ]);

        $event = WorkflowNotificationEvent::query()->create([
            'workflow_notification_rule_id' => $rule->getKey(),
            'content_area' => AdminAccess::PAGES,
            'trigger' => WorkflowNotificationRule::TRIGGER_UPDATED,
            'status' => WorkflowNotificationEvent::STATUS_PENDING,
            'record_key' => Page::class.':1',
            'record_label' => 'About Us',
            'actor_id' => $actor->getKey(),
            'actor_name' => $actor->name,
            'scheduled_at' => now(),
            'recipient_emails' => ['page-review@example.com'],
        ]);

        $mail = new WorkflowNotificationMail($event);
        $html = $mail->render();

        $this->assertSame('gFree Church: About Us Updated by Editor Person', $mail->envelope()->subject);
        $this->assertStringContainsString('Site gFree Church saw About Us get Updated on Jun 15, 2026 at 2:35 PM.', $html);
        $this->assertStringContainsString('Contact editor@example.com. Full: Jun 15, 2026 2:35 PM.', $html);

        Carbon::setTestNow();
    }

    public function test_delete_workflow_notification_email_shows_most_recent_page_screenshot(): void
    {
        $this->mockVisualSnapshots();

        $rule = WorkflowNotificationRule::query()->create([
            'name' => 'Page deleted',
            'content_area' => AdminAccess::PAGES,
            'triggers' => [WorkflowNotificationRule::TRIGGER_DELETED],
            'extra_emails' => 'page-review@example.com',
            'subject' => 'Page deleted',
            'message' => 'Please note this page was deleted.',
            'delay_minutes' => 15,
            'is_enabled' => true,
        ]);

        $event = WorkflowNotificationEvent::query()->create([
            'workflow_notification_rule_id' => $rule->getKey(),
            'content_area' => AdminAccess::PAGES,
            'trigger' => WorkflowNotificationRule::TRIGGER_DELETED,
            'status' => WorkflowNotificationEvent::STATUS_PENDING,
            'record_key' => Page::class.':1',
            'pre_snapshot_path' => 'page-visual-snapshots/deleted-most-recent.png',
            'post_snapshot_path' => 'page-visual-snapshots/should-not-show.png',
            'scheduled_at' => now(),
            'recipient_emails' => ['page-review@example.com'],
        ]);

        $html = view('mail.workflow-notification', ['event' => $event])->render();

        $this->assertStringContainsString('Visual snapshot', $html);
        $this->assertStringNotContainsString('Visual comparison', $html);
        $this->assertStringContainsString('Most Recent Page Screenshot', $html);
        $this->assertStringContainsString('https://example.test/snapshots/page-visual-snapshots/deleted-most-recent.png', $html);
        $this->assertStringNotContainsString('https://example.test/snapshots/page-visual-snapshots/should-not-show.png', $html);
    }

    public function test_deleted_page_notification_uses_existing_visual_baseline(): void
    {
        Queue::fake();
        $this->mockVisualSnapshots();

        $recipient = User::factory()->create([
            'email' => 'page-review@example.com',
        ]);

        WorkflowNotificationRule::query()->create([
            'name' => 'Page deleted',
            'content_area' => AdminAccess::PAGES,
            'triggers' => [WorkflowNotificationRule::TRIGGER_DELETED],
            'selected_user_ids' => [$recipient->getKey()],
            'subject' => 'Page deleted',
            'message' => 'Please note this page was deleted.',
            'delay_minutes' => 15,
            'is_enabled' => true,
        ]);

        $page = Page::query()->create([
            'title' => 'Deleted Page',
            'slug' => 'deleted-page',
            'is_published' => true,
        ]);

        WorkflowVisualSnapshot::query()->create([
            'snapshotable_type' => Page::class,
            'snapshotable_id' => $page->getKey(),
            'snapshot_path' => 'page-visual-snapshots/delete-baseline.png',
            'snapshot_captured_at' => now()->subHour(),
        ]);

        $page->delete();

        app(WorkflowNotificationService::class)->automaticForRecord($page, WorkflowNotificationRule::TRIGGER_DELETED);

        $event = WorkflowNotificationEvent::query()->firstOrFail();

        $this->assertSame('page-visual-snapshots/delete-baseline.png', $event->pre_snapshot_path);
        $this->assertNull($event->post_snapshot_path);

        Queue::assertPushed(SendWorkflowNotificationJob::class);
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

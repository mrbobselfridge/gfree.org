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
use App\Support\AdminAccess;
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

        Queue::assertPushed(SendWorkflowNotificationJob::class);
    }
}

<?php

namespace App\Jobs;

use App\Models\WorkflowNotificationEvent;
use App\Support\WorkflowNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWorkflowNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $eventId) {}

    public function handle(WorkflowNotificationService $service): void
    {
        $event = WorkflowNotificationEvent::query()->find($this->eventId);

        if (! $event) {
            return;
        }

        $service->send($event);
    }
}

<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Models\WorkflowNotificationEvent;
use App\Models\WorkflowNotificationRule;

class WorkflowNotificationTemplate
{
    /**
     * @return array<string, string>
     */
    public static function variables(WorkflowNotificationEvent $event): array
    {
        $now = now();
        $siteName = SiteSetting::query()->value('church_name') ?: config('app.name', 'TwyxtCo');
        $actor = $event->actor;

        return [
            'church_name' => $siteName,
            'site_name' => $siteName,
            'current_date' => $now->format('M j, Y'),
            'current_time' => $now->format('g:i A'),
            'current_datetime' => $now->format('M j, Y g:i A'),
            'page_title' => (string) ($event->record_label ?? ''),
            'action_status' => self::actionStatus($event->trigger),
            'updater_name' => (string) ($event->actor_name ?: $actor?->name ?: ''),
            'updater_email' => (string) ($actor?->email ?? ''),
        ];
    }

    public static function render(?string $template, WorkflowNotificationEvent $event): string
    {
        $template = (string) $template;

        if ($template === '') {
            return '';
        }

        return strtr(
            $template,
            collect(self::variables($event))
                ->mapWithKeys(fn (string $value, string $key): array => ['{'.$key.'}' => $value])
                ->all(),
        );
    }

    public static function actionStatus(?string $trigger): string
    {
        return match ($trigger) {
            WorkflowNotificationRule::TRIGGER_CREATED => 'Created',
            WorkflowNotificationRule::TRIGGER_UPDATED => 'Updated',
            WorkflowNotificationRule::TRIGGER_MANUAL => 'Manually Sent',
            WorkflowNotificationRule::TRIGGER_DELETED => 'Deleted',
            default => str((string) $trigger)->headline()->toString(),
        };
    }
}

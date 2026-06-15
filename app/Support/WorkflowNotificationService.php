<?php

namespace App\Support;

use App\Contracts\HasPublicUrl;
use App\Jobs\SendWorkflowNotificationJob;
use App\Mail\WorkflowNotificationMail;
use App\Models\User;
use App\Models\WorkflowNotificationEvent;
use App\Models\WorkflowNotificationRule;
use App\Models\WorkflowVisualSnapshot;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class WorkflowNotificationService
{
    public function __construct(private readonly WorkflowVisualSnapshots $visualSnapshots) {}

    public function automaticForRecord(Model $record, string $trigger, ?User $actor = null): void
    {
        $area = WorkflowNotificationAreas::areaForModel($record);

        if (! $area) {
            return;
        }

        if ($trigger === WorkflowNotificationRule::TRIGGER_CREATED && $this->hasAnyRuleForArea($area)) {
            $this->visualSnapshots->seedBaseline($record);
        }

        $deleteSnapshot = $trigger === WorkflowNotificationRule::TRIGGER_DELETED
            ? $this->visualSnapshots->baselineFor($record)
            : null;

        $this->automatic(
            area: $area,
            trigger: $trigger,
            recordKey: $this->recordKey($record),
            recordLabel: WorkflowNotificationAreas::labelForRecord($record),
            actor: $actor ?? $this->currentUser(),
            adminUrl: WorkflowNotificationAreas::adminUrlForRecord($record),
            publicUrl: $record instanceof HasPublicUrl ? $record->publicUrl() : null,
            recordType: $record::class,
            recordId: (int) $record->getKey(),
            preSnapshot: $deleteSnapshot,
        );
    }

    public function automatic(
        string $area,
        string $trigger,
        string $recordKey,
        ?string $recordLabel = null,
        ?User $actor = null,
        ?string $adminUrl = null,
        ?string $publicUrl = null,
        ?string $recordType = null,
        ?int $recordId = null,
        ?WorkflowVisualSnapshot $preSnapshot = null,
    ): void {
        $this->rulesFor($area, $trigger)
            ->each(function (WorkflowNotificationRule $rule) use ($area, $trigger, $recordKey, $recordLabel, $actor, $adminUrl, $publicUrl, $recordType, $recordId, $preSnapshot): void {
                $recipients = $rule->recipientEmails();

                if ($recipients->isEmpty()) {
                    return;
                }

                $scheduledAt = now()->addMinutes(max(0, $rule->delay_minutes));

                $event = WorkflowNotificationEvent::query()
                    ->where('workflow_notification_rule_id', $rule->getKey())
                    ->where('record_key', $recordKey)
                    ->where('status', WorkflowNotificationEvent::STATUS_PENDING)
                    ->first();

                $isNewEvent = ! $event;
                $event ??= new WorkflowNotificationEvent([
                    'workflow_notification_rule_id' => $rule->getKey(),
                    'content_area' => $area,
                    'record_key' => $recordKey,
                ]);

                if ($isNewEvent || ($this->shouldUsePreSnapshot($trigger) && blank($event->pre_snapshot_path))) {
                    $preSnapshot
                        ? $this->fillPreSnapshotFromBaseline($event, $preSnapshot)
                        : $this->fillPreSnapshot($event, $recordType, $recordId);
                }

                $event->fill([
                    'trigger' => $trigger,
                    'status' => WorkflowNotificationEvent::STATUS_PENDING,
                    'record_type' => $recordType,
                    'record_id' => $recordId,
                    'record_label' => $recordLabel,
                    'actor_id' => $actor?->getKey(),
                    'actor_name' => $actor?->name,
                    'admin_url' => $adminUrl,
                    'public_url' => $publicUrl,
                    'post_snapshot_path' => null,
                    'post_snapshot_captured_at' => null,
                    'scheduled_at' => $scheduledAt,
                    'sent_at' => null,
                    'cancelled_at' => null,
                    'recipient_emails' => $recipients->all(),
                ])->save();

                SendWorkflowNotificationJob::dispatch($event->getKey())->delay($scheduledAt);
            });
    }

    public function manualForRecord(Model $record, array $ruleIds, ?User $actor = null): int
    {
        $area = WorkflowNotificationAreas::areaForModel($record);

        if (! $area) {
            return 0;
        }

        return $this->manual(
            area: $area,
            ruleIds: $ruleIds,
            recordKey: $this->recordKey($record),
            recordLabel: WorkflowNotificationAreas::labelForRecord($record),
            actor: $actor ?? $this->currentUser(),
            adminUrl: WorkflowNotificationAreas::adminUrlForRecord($record),
            publicUrl: $record instanceof HasPublicUrl ? $record->publicUrl() : null,
            recordType: $record::class,
            recordId: (int) $record->getKey(),
        );
    }

    public function manual(
        string $area,
        array $ruleIds,
        string $recordKey,
        ?string $recordLabel = null,
        ?User $actor = null,
        ?string $adminUrl = null,
        ?string $publicUrl = null,
        ?string $recordType = null,
        ?int $recordId = null,
    ): int {
        $rules = WorkflowNotificationRule::query()
            ->enabled()
            ->where('content_area', $area)
            ->whereKey($ruleIds)
            ->get()
            ->filter(fn (WorkflowNotificationRule $rule): bool => $rule->hasTrigger(WorkflowNotificationRule::TRIGGER_MANUAL));

        $sent = 0;

        foreach ($rules as $rule) {
            $recipients = $rule->recipientEmails();

            if ($recipients->isEmpty()) {
                continue;
            }

            WorkflowNotificationEvent::query()
                ->where('workflow_notification_rule_id', $rule->getKey())
                ->where('record_key', $recordKey)
                ->where('status', WorkflowNotificationEvent::STATUS_PENDING)
                ->update([
                    'status' => WorkflowNotificationEvent::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                ]);

            $event = WorkflowNotificationEvent::query()->create([
                'workflow_notification_rule_id' => $rule->getKey(),
                'content_area' => $area,
                'trigger' => WorkflowNotificationRule::TRIGGER_MANUAL,
                'status' => WorkflowNotificationEvent::STATUS_PENDING,
                'record_type' => $recordType,
                'record_id' => $recordId,
                'record_key' => $recordKey,
                'record_label' => $recordLabel,
                'actor_id' => $actor?->getKey(),
                'actor_name' => $actor?->name,
                'admin_url' => $adminUrl,
                'public_url' => $publicUrl,
                'scheduled_at' => now(),
                'recipient_emails' => $recipients->all(),
            ]);

            $this->fillPreSnapshot($event, $recordType, $recordId);
            $event->save();

            $this->send($event);
            $sent++;
        }

        return $sent;
    }

    public function send(WorkflowNotificationEvent $event): bool
    {
        if ($event->status !== WorkflowNotificationEvent::STATUS_PENDING) {
            return false;
        }

        if ($event->scheduled_at && $event->scheduled_at->isFuture()) {
            SendWorkflowNotificationJob::dispatch($event->getKey())->delay($event->scheduled_at);

            return false;
        }

        $event->loadMissing(['actor', 'rule']);

        $rule = $event->rule;
        $recipients = collect($event->recipient_emails ?: $rule?->recipientEmails()?->all() ?: [])
            ->filter()
            ->unique()
            ->values();

        if (! $rule || $recipients->isEmpty()) {
            $event->update([
                'status' => WorkflowNotificationEvent::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            return false;
        }

        if ($this->shouldRenderComparison($event) && blank($event->pre_snapshot_path)) {
            $this->fillPreSnapshot($event, $event->record_type, $event->record_id);
            $event->save();
        }

        $postSnapshot = $this->capturePostSnapshot($event);

        if ($postSnapshot && $this->shouldRenderComparison($event) && blank($event->pre_snapshot_path)) {
            $event->forceFill([
                'pre_snapshot_path' => $postSnapshot->path,
                'pre_snapshot_captured_at' => $event->post_snapshot_captured_at ?? now(),
            ])->save();
        }

        $event->refresh()->loadMissing(['actor', 'rule']);

        Mail::to($recipients->all())->send(new WorkflowNotificationMail($event));

        $event->update([
            'status' => WorkflowNotificationEvent::STATUS_SENT,
            'sent_at' => now(),
        ]);

        if ($postSnapshot && ($record = $this->visualSnapshots->recordFor($event->record_type, $event->record_id))) {
            $this->visualSnapshots->advanceBaseline($record, $postSnapshot);
        }

        return true;
    }

    public function manualRuleOptionsForRecord(Model $record): array
    {
        $area = WorkflowNotificationAreas::areaForModel($record);

        if (! $area) {
            return [];
        }

        return $this->rulesFor($area, WorkflowNotificationRule::TRIGGER_MANUAL)
            ->pluck('name', 'id')
            ->all();
    }

    public function prepareDeletedRecordSnapshot(Model $record): void
    {
        $area = WorkflowNotificationAreas::areaForModel($record);

        if (! $area || $this->rulesFor($area, WorkflowNotificationRule::TRIGGER_DELETED)->isEmpty()) {
            return;
        }

        $this->visualSnapshots->captureBaseline($record);
    }

    public function rulesFor(string $area, string $trigger): Collection
    {
        return WorkflowNotificationRule::query()
            ->enabled()
            ->where('content_area', $area)
            ->get()
            ->filter(fn (WorkflowNotificationRule $rule): bool => $rule->hasTrigger($trigger))
            ->values();
    }

    private function shouldRenderComparison(WorkflowNotificationEvent $event): bool
    {
        return in_array($event->trigger, [
            WorkflowNotificationRule::TRIGGER_UPDATED,
            WorkflowNotificationRule::TRIGGER_MANUAL,
        ], true);
    }

    private function shouldUsePreSnapshot(string $trigger): bool
    {
        return in_array($trigger, [
            WorkflowNotificationRule::TRIGGER_UPDATED,
            WorkflowNotificationRule::TRIGGER_MANUAL,
            WorkflowNotificationRule::TRIGGER_DELETED,
        ], true);
    }

    private function hasAnyRuleForArea(string $area): bool
    {
        return WorkflowNotificationRule::query()
            ->enabled()
            ->where('content_area', $area)
            ->exists();
    }

    private function fillPreSnapshot(WorkflowNotificationEvent $event, ?string $recordType, ?int $recordId): void
    {
        $record = $this->visualSnapshots->recordFor($recordType, $recordId);

        if (! $record) {
            return;
        }

        $baseline = $this->visualSnapshots->seedBaseline($record);

        if (! $baseline) {
            return;
        }

        $event->fill([
            'pre_snapshot_path' => $baseline->snapshot_path,
            'pre_snapshot_captured_at' => $baseline->snapshot_captured_at,
        ]);
    }

    private function fillPreSnapshotFromBaseline(WorkflowNotificationEvent $event, WorkflowVisualSnapshot $baseline): void
    {
        $event->fill([
            'pre_snapshot_path' => $baseline->snapshot_path,
            'pre_snapshot_captured_at' => $baseline->snapshot_captured_at,
        ]);
    }

    private function capturePostSnapshot(WorkflowNotificationEvent $event): ?PageVisualSnapshotResult
    {
        $record = $this->visualSnapshots->recordFor($event->record_type, $event->record_id);

        if (! $record) {
            return null;
        }

        $snapshot = $this->visualSnapshots->captureCurrent($record);

        if (! $snapshot) {
            return null;
        }

        $event->forceFill([
            'post_snapshot_path' => $snapshot->path,
            'post_snapshot_captured_at' => now(),
        ])->save();

        return $snapshot;
    }

    private function recordKey(Model $record): string
    {
        return $record::class.':'.$record->getKey();
    }

    private function currentUser(): ?User
    {
        $filamentUser = Filament::auth()->user();

        if ($filamentUser instanceof User) {
            return $filamentUser;
        }

        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}

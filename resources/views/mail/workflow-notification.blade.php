@php
    $rule = $event->rule;
    $areaLabel = \App\Support\WorkflowNotificationAreas::options()[$event->content_area] ?? str($event->content_area)->headline();
    $triggerLabel = \App\Support\WorkflowNotificationTemplate::actionStatus($event->trigger);
    $snapshotService = app(\App\Support\PageVisualSnapshot::class);
    $isCreateTrigger = $event->trigger === \App\Models\WorkflowNotificationRule::TRIGGER_CREATED;
    $isDeleteTrigger = $event->trigger === \App\Models\WorkflowNotificationRule::TRIGGER_DELETED;
    $isManualTrigger = $event->trigger === \App\Models\WorkflowNotificationRule::TRIGGER_MANUAL;
    $message = \App\Support\WorkflowNotificationTemplate::render($rule->message, $event);
    $manualMessage = \App\Support\WorkflowNotificationTemplate::render($event->manual_message, $event);

    $preSnapshotUrl = (! $isCreateTrigger && ! $isDeleteTrigger && ! $isManualTrigger && $event->pre_snapshot_path) ? $snapshotService->imageUrl($event->pre_snapshot_path) : null;
    $postSnapshotUrl = (! $isDeleteTrigger && $event->post_snapshot_path) ? $snapshotService->imageUrl($event->post_snapshot_path) : null;
    $deleteSnapshotUrl = ($isDeleteTrigger && $event->pre_snapshot_path) ? $snapshotService->imageUrl($event->pre_snapshot_path) : null;
    $hasBothSnapshots = $preSnapshotUrl && $postSnapshotUrl;
    $singleSnapshotUrl = $deleteSnapshotUrl ?: ($postSnapshotUrl ?: $preSnapshotUrl);
    $preSnapshotLabel = 'Before Changes';
    $postSnapshotLabel = $isCreateTrigger ? 'Saved Created Page' : 'After Changes';
    $singleSnapshotLabel = $isCreateTrigger ? 'Saved Created Page' : ($isDeleteTrigger ? 'Most Recent Page Screenshot' : $postSnapshotLabel);
@endphp

<p>{!! nl2br(e($message)) !!}</p>

@if ($isManualTrigger && filled($manualMessage))
    <p>{!! nl2br(e($manualMessage)) !!}</p>
@endif

@unless ($isManualTrigger)
    <hr>

    <p>
        <strong>Content area:</strong> {{ $areaLabel }}<br>
        <strong>Action:</strong> {{ $triggerLabel }}<br>
        @if ($event->record_label)
            <strong>Item:</strong> {{ $event->record_label }}<br>
        @endif
        @if ($event->actor_name)
            <strong>Changed by:</strong> {{ $event->actor_name }}<br>
        @endif
    </p>
@endunless

@if ($event->public_url || $event->admin_url)
    <p>
        @if ($event->public_url)
            <a href="{{ $event->public_url }}">View</a><br>
        @endif

        @if ($event->admin_url)
            <a href="{{ $event->admin_url }}">Open in admin</a>
        @endif
    </p>
@endif

@if ($isManualTrigger && $event->public_url)
    <p>
        <strong>Full URL:</strong><br>
        <a href="{{ $event->public_url }}">{{ $event->public_url }}</a>
    </p>
@endif

@if ($isManualTrigger && $singleSnapshotUrl)
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width: 100%; max-width: 100%; border-collapse: collapse; table-layout: fixed;">
        <tr>
            <td width="100%" valign="top" style="width: 100%; max-width: 100%; padding: 0 0 12px 0; vertical-align: top;">
                <a href="{{ $singleSnapshotUrl }}">
                    <img src="{{ $singleSnapshotUrl }}" alt="Current page screenshot" width="100%" style="display: block; width: 100%; max-width: 100%; height: auto; border: 1px solid #d1d5db;">
                </a>
            </td>
        </tr>
    </table>
@elseif ($hasBothSnapshots || $singleSnapshotUrl)
    <hr>

    <p><strong>{{ $hasBothSnapshots ? 'Visual comparison' : 'Visual snapshot' }}</strong></p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width: 100%; max-width: 100%; border-collapse: collapse; table-layout: fixed;">
        <tr>
            @if ($hasBothSnapshots)
                <td width="50%" valign="top" style="width: 50%; max-width: 50%; padding: 0 8px 12px 0; vertical-align: top;">
                    <p style="margin: 0 0 8px; font-weight: 700;">{{ $preSnapshotLabel }}</p>
                    <a href="{{ $preSnapshotUrl }}">
                        <img src="{{ $preSnapshotUrl }}" alt="{{ $preSnapshotLabel }} page screenshot" width="100%" style="display: block; width: 100%; max-width: 100%; height: auto; border: 1px solid #d1d5db;">
                    </a>
                </td>

                <td width="50%" valign="top" style="width: 50%; max-width: 50%; padding: 0 0 12px 8px; vertical-align: top;">
                    <p style="margin: 0 0 8px; font-weight: 700;">{{ $postSnapshotLabel }}</p>
                    <a href="{{ $postSnapshotUrl }}">
                        <img src="{{ $postSnapshotUrl }}" alt="{{ $postSnapshotLabel }} page screenshot" width="100%" style="display: block; width: 100%; max-width: 100%; height: auto; border: 1px solid #d1d5db;">
                    </a>
                </td>
            @elseif ($singleSnapshotUrl)
                <td width="100%" valign="top" style="width: 100%; max-width: 100%; padding: 0 0 12px 0; vertical-align: top;">
                    <p style="margin: 0 0 8px; font-weight: 700;">{{ $singleSnapshotLabel }}</p>
                    <a href="{{ $singleSnapshotUrl }}">
                        <img src="{{ $singleSnapshotUrl }}" alt="{{ $singleSnapshotLabel }} page screenshot" width="100%" style="display: block; width: 100%; max-width: 100%; height: auto; border: 1px solid #d1d5db;">
                    </a>
                </td>
            @endif
        </tr>
    </table>
@endif

@php
    $rule = $event->rule;
    $areaLabel = \App\Support\WorkflowNotificationAreas::options()[$event->content_area] ?? str($event->content_area)->headline();
    $triggerLabel = \App\Support\WorkflowNotificationAreas::triggerOptions()[$event->trigger] ?? str($event->trigger)->headline();
    $snapshotService = app(\App\Support\PageVisualSnapshot::class);
    $isCreateTrigger = $event->trigger === \App\Models\WorkflowNotificationRule::TRIGGER_CREATED;
    $preSnapshotUrl = (! $isCreateTrigger && $event->pre_snapshot_path) ? $snapshotService->imageUrl($event->pre_snapshot_path) : null;
    $postSnapshotUrl = $event->post_snapshot_path ? $snapshotService->imageUrl($event->post_snapshot_path) : null;
    $hasBothSnapshots = $preSnapshotUrl && $postSnapshotUrl;
@endphp

<p>{!! nl2br(e($rule->message)) !!}</p>

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

@if ($preSnapshotUrl || $postSnapshotUrl)
    <hr>

    <p><strong>{{ $hasBothSnapshots ? 'Visual comparison' : 'Visual snapshot' }}</strong></p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width: 100%; max-width: 100%; border-collapse: collapse; table-layout: fixed;">
        <tr>
            @if ($preSnapshotUrl)
                <td width="{{ $hasBothSnapshots ? '50%' : '100%' }}" valign="top" style="width: {{ $hasBothSnapshots ? '50%' : '100%' }}; max-width: {{ $hasBothSnapshots ? '50%' : '100%' }}; padding: 0 8px 12px 0; vertical-align: top;">
                    <p style="margin: 0 0 8px; font-weight: 700;">PRE</p>
                    <a href="{{ $preSnapshotUrl }}">
                        <img src="{{ $preSnapshotUrl }}" alt="PRE page screenshot" width="100%" style="display: block; width: 100%; max-width: 100%; height: auto; border: 1px solid #d1d5db;">
                    </a>
                </td>
            @endif

            @if ($postSnapshotUrl)
                <td width="{{ $hasBothSnapshots ? '50%' : '100%' }}" valign="top" style="width: {{ $hasBothSnapshots ? '50%' : '100%' }}; max-width: {{ $hasBothSnapshots ? '50%' : '100%' }}; padding: {{ $hasBothSnapshots ? '0 0 12px 8px' : '0 0 12px 0' }}; vertical-align: top;">
                    @if ($hasBothSnapshots)
                        <p style="margin: 0 0 8px; font-weight: 700;">POST</p>
                    @endif
                    <a href="{{ $postSnapshotUrl }}">
                        <img src="{{ $postSnapshotUrl }}" alt="{{ $hasBothSnapshots ? 'POST page screenshot' : 'Page screenshot' }}" width="100%" style="display: block; width: 100%; max-width: 100%; height: auto; border: 1px solid #d1d5db;">
                    </a>
                </td>
            @endif
        </tr>
    </table>
@endif

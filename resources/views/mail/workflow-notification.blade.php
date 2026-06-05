@php
    $rule = $event->rule;
    $areaLabel = \App\Support\WorkflowNotificationAreas::options()[$event->content_area] ?? str($event->content_area)->headline();
    $triggerLabel = \App\Support\WorkflowNotificationAreas::triggerOptions()[$event->trigger] ?? str($event->trigger)->headline();
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

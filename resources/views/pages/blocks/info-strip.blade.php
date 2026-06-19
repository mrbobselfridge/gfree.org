@php
    $items = $data['items'] ?? [];
    $spacing = $data['spacing'] ?? 'bottom';
    $contentWidth = match ($data['content_width'] ?? 'wide') {
        'small' => 'small',
        'medium', 'normal' => 'medium',
        default => 'wide',
    };
@endphp

@if (filled($items))
    <section
        @class([
            'concept-service-strip',
            'page-block--info-strip',
            'page-block--info-strip-spacing-' . $spacing,
            'page-block--info-strip-width-' . $contentWidth,
        ])
        style="--info-strip-count: {{ count($items) }}"
        aria-label="Service details"
    >
        @foreach ($items as $item)
            <div class="concept-service-strip__item">
                <span>{{ $item['label'] }}</span>
                <div class="concept-service-strip__value">{!! \App\Support\RichContent::render($item['value']) !!}</div>
            </div>
        @endforeach
    </section>
@endif

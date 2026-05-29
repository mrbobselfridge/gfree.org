@php($items = $data['items'] ?? [])
@php($spacing = $data['spacing'] ?? 'bottom')

@if (filled($items))
    <section
        @class([
            'concept-service-strip',
            'page-block--info-strip',
            'page-block--info-strip-spacing-' . $spacing,
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

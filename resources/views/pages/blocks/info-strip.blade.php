@php
    $items = $data['items'] ?? [];
    $spacing = $data['spacing'] ?? 'bottom';
    $contentWidth = match ($data['content_width'] ?? 'wide') {
        'small' => 'small',
        'medium', 'normal' => 'medium',
        default => 'wide',
    };
    $background = \App\Support\SiteDesignPalette::backgroundKey($data['background'] ?? 'white');
    $backgroundStyle = \App\Support\SiteDesignPalette::pageBlockStyle($background);
    $backgroundTarget = ($data['background_target'] ?? 'item') === 'page' ? 'page' : 'item';
@endphp

@if (filled($items))
    @if ($backgroundTarget === 'page')
        <section
            @class([
                'page-block',
                'page-block--bg-' . $background,
                'page-block--info-strip-page',
                'page-block--info-strip-spacing-' . $spacing,
            ])
            @if ($backgroundStyle)
                style="{{ $backgroundStyle }}"
            @endif
        >
            <div
                @class([
                    'concept-service-strip',
                    'page-block--info-strip',
                    'page-block--info-strip-target-page',
                    'page-block--info-strip-width-' . $contentWidth,
                ])
                style="--info-strip-count: {{ count($items) }}"
                aria-label="Service details"
            >
                @foreach ($items as $item)
                    <div class="concept-service-strip__item">
                        <span>{!! \App\Support\SiteVariables::renderText($item['label'], $settings ?? null) !!}</span>
                        <div class="concept-service-strip__value">{!! \App\Support\RichContent::render($item['value']) !!}</div>
                    </div>
                @endforeach
            </div>
        </section>
    @else
        <section
            @class([
                'concept-service-strip',
                'page-block--bg-' . $background,
                'page-block--info-strip',
                'page-block--info-strip-target-item',
                'page-block--info-strip-spacing-' . $spacing,
                'page-block--info-strip-width-' . $contentWidth,
            ])
            style="--info-strip-count: {{ count($items) }}; {{ $backgroundStyle }}"
            aria-label="Service details"
        >
            @foreach ($items as $item)
                <div class="concept-service-strip__item">
                    <span>{!! \App\Support\SiteVariables::renderText($item['label'], $settings ?? null) !!}</span>
                    <div class="concept-service-strip__value">{!! \App\Support\RichContent::render($item['value']) !!}</div>
                </div>
            @endforeach
        </section>
    @endif
@endif

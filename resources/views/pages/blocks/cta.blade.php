@php
    $background = match ($data['background'] ?? $data['style'] ?? 'black') {
        'dark' => 'black',
        'light' => 'white',
        default => $data['background'] ?? $data['style'] ?? 'black',
    };

    $layout = $data['layout'] ?? 'content_left';
    $body = $data['body'] ?? null;
    $bodyHasHtml = filled($body) && $body !== strip_tags($body);
@endphp

<section @class([
    'page-block',
    'page-block--cta',
    'page-block--bg-' . $background,
    'page-block--cta-content-right' => $layout === 'content_right',
    'page-block--cta-button-top' => $layout === 'button_top',
    'page-block--cta-button-bottom' => $layout === 'button_bottom',
])>
    <div class="page-block__inner page-cta">
        <div>
            @if (filled($data['eyebrow'] ?? null))
                <p class="page-block__eyebrow">{{ $data['eyebrow'] }}</p>
            @endif

            @if (filled($data['heading'] ?? null))
                <h2>{{ $data['heading'] }}</h2>
            @endif

            @if (filled($data['body'] ?? null))
                <div class="page-rich-text page-cta__body">
                    @if ($bodyHasHtml)
                        {!! $body !!}
                    @else
                        <p>{!! nl2br(e($body)) !!}</p>
                    @endif
                </div>
            @endif
        </div>

        @if (filled($data['button_label'] ?? null) && filled($data['button_url'] ?? null))
            <a class="page-block__button" href="{{ $data['button_url'] }}">{{ $data['button_label'] }}</a>
        @endif
    </div>
</section>

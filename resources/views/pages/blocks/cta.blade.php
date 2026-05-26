@php
    $background = match ($data['background'] ?? $data['style'] ?? 'black') {
        'dark' => 'black',
        'light' => 'white',
        default => $data['background'] ?? $data['style'] ?? 'black',
    };
@endphp

<section @class([
    'page-block',
    'page-block--cta',
    'page-block--bg-' . $background,
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
                <p>{!! nl2br(e($data['body'])) !!}</p>
            @endif
        </div>

        @if (filled($data['button_label'] ?? null) && filled($data['button_url'] ?? null))
            <a class="page-block__button" href="{{ $data['button_url'] }}">{{ $data['button_label'] }}</a>
        @endif
    </div>
</section>

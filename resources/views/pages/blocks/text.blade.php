@php
    $background = match ($data['background'] ?? 'white') {
        'dark' => 'black',
        'light' => 'white',
        default => $data['background'] ?? 'white',
    };

    $contentWidth = match ($data['content_width'] ?? 'medium') {
        'small' => 'small',
        'wide' => 'wide',
        'normal', 'medium' => 'medium',
        default => 'medium',
    };
@endphp

<section @class([
    'page-block',
    'page-block--text',
    'page-block--bg-' . $background,
])>
    <div @class([
        'page-block__inner',
        'page-block__inner--text-' . $contentWidth,
    ])>
        @if (filled($data['eyebrow'] ?? null))
            <p class="page-block__eyebrow">{{ $data['eyebrow'] }}</p>
        @endif

        @if (filled($data['heading'] ?? null))
            <h2>{{ $data['heading'] }}</h2>
        @endif

        @if (filled($data['body'] ?? null))
            <div class="page-rich-text">
                {!! \App\Support\RichContent::render($data['body']) !!}
            </div>
        @endif
    </div>
</section>

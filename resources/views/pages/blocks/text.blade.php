@php
    $background = \App\Support\SiteDesignPalette::backgroundKey($data['background'] ?? 'white');
    $backgroundStyle = \App\Support\SiteDesignPalette::pageBlockStyle($background);

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
])
    @if ($backgroundStyle)
        style="{{ $backgroundStyle }}"
    @endif
>
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

@php
    $background = \App\Support\SiteDesignPalette::backgroundKey($data['background'] ?? 'white');
    $backgroundStyle = \App\Support\SiteDesignPalette::pageBlockStyle($background);
    $contentWidth = match ($data['content_width'] ?? 'medium') {
        'small' => 'small',
        'wide' => 'wide',
        default => 'medium',
    };
@endphp

<section @class(['page-block', 'page-block--embed', 'page-block--bg-' . $background])
    @if ($backgroundStyle)
        style="{{ $backgroundStyle }}"
    @endif
>
    <div @class(['page-block__inner', 'page-block__inner--text-' . $contentWidth])>
        @if (filled($data['heading'] ?? null))
            <h2>{{ $data['heading'] }}</h2>
        @endif

        @if (filled($data['embed_code'] ?? null))
            <div class="page-rich-embed">
                {!! $data['embed_code'] !!}
            </div>
        @endif
    </div>
</section>

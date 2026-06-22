@php
    $background = \App\Support\SiteDesignPalette::backgroundKey($data['background'] ?? 'white');
    $backgroundStyle = \App\Support\SiteDesignPalette::pageBlockStyle($background);

    $contentWidth = match ($data['content_width'] ?? 'medium') {
        'small' => 'small',
        'wide' => 'wide',
        'normal', 'medium' => 'medium',
        default => 'medium',
    };
    $hasEyebrow = \App\Support\RichContent::hasRenderableContent($data['eyebrow'] ?? null);
    $hasHeading = \App\Support\RichContent::hasRenderableContent($data['heading'] ?? null);
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
        @if ($hasEyebrow)
            <p class="page-block__eyebrow">{!! \App\Support\SiteVariables::renderText($data['eyebrow'], $settings ?? null) !!}</p>
        @endif

        @if ($hasHeading)
            <h2>{!! \App\Support\SiteVariables::renderText($data['heading'], $settings ?? null) !!}</h2>
        @endif

        @if (filled($data['body'] ?? null))
            <div class="page-rich-text">
                {!! \App\Support\RichContent::render($data['body']) !!}
            </div>
        @endif
    </div>
</section>

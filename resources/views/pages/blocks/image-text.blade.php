@php
    $background = \App\Support\SiteDesignPalette::backgroundKey($data['background'] ?? 'white');
    $backgroundStyle = \App\Support\SiteDesignPalette::pageBlockStyle($background);
    $imagePosition = match ($data['image_position'] ?? 'left') {
        'center' => 'full_width',
        default => $data['image_position'] ?? 'left',
    };
    $contentWidth = match ($data['content_width'] ?? 'wide') {
        'small' => 'small',
        'medium', 'normal' => 'medium',
        default => 'wide',
    };

    $hasContent = \App\Support\RichContent::hasRenderableContent($data['eyebrow'] ?? null)
        || \App\Support\RichContent::hasRenderableContent($data['heading'] ?? null)
        || \App\Support\RichContent::hasRenderableContent($data['body'] ?? null)
        || (filled($data['button_label'] ?? null) && filled($data['button_url'] ?? null));
@endphp

<section @class([
    'page-block',
    'page-block--image-text',
    'page-block--bg-' . $background,
    'page-block--image-right' => in_array($imagePosition, ['right', 'right_top'], true),
    'page-block--image-left-top' => $imagePosition === 'left_top',
    'page-block--image-right-top' => $imagePosition === 'right_top',
    'page-block--image-top' => $imagePosition === 'top',
    'page-block--image-bottom' => $imagePosition === 'bottom',
    'page-block--image-full' => $imagePosition === 'full_width',
    'page-block--image-screenwidth' => $imagePosition === 'screen_width',
    'page-block--image-only' => ! $hasContent,
])
    @if ($backgroundStyle)
        style="{{ $backgroundStyle }}"
    @endif
>
    <div @class([
        'page-block__inner',
        'page-block__inner--text-' . $contentWidth => $imagePosition !== 'screen_width',
        'page-image-text',
    ])>
        @if (filled($data['image_url'] ?? null))
            <img src="{{ $data['image_url'] }}" alt="{{ $data['image_alt'] ?? '' }}">
        @endif

        @if ($hasContent)
        <div class="page-image-text__content">
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

            @if (filled($data['button_label'] ?? null) && filled($data['button_url'] ?? null))
                <a class="page-block__button" href="{{ $data['button_url'] }}"{!! \App\Support\LinkAttributes::externalAttributes($data['button_url']) !!}>{{ $data['button_label'] }}</a>
            @endif
        </div>
        @endif
    </div>
</section>

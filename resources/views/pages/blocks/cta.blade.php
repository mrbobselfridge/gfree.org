@php
    $background = \App\Support\SiteDesignPalette::backgroundKey($data['background'] ?? $data['style'] ?? 'black');
    $backgroundStyle = \App\Support\SiteDesignPalette::pageBlockStyle($background);

    $layout = $data['layout'] ?? 'content_left';
    $contentWidth = match ($data['content_width'] ?? 'medium') {
        'small' => 'small',
        'wide', 'large' => 'wide',
        default => 'medium',
    };
    $body = $data['body'] ?? null;
    $bodyHasHtml = filled($body) && $body !== strip_tags($body);
    $hasEyebrow = \App\Support\RichContent::hasRenderableContent($data['eyebrow'] ?? null);
    $hasHeading = \App\Support\RichContent::hasRenderableContent($data['heading'] ?? null);
    $hasBody = \App\Support\RichContent::hasRenderableContent($body);
    $hasTextContent = $hasEyebrow || $hasHeading || $hasBody;
@endphp

<section @class([
    'page-block',
    'page-block--cta',
    'page-block--bg-' . $background,
    'page-block--cta-content-right' => $layout === 'content_right',
    'page-block--cta-button-top' => $layout === 'button_top',
    'page-block--cta-button-bottom' => $layout === 'button_bottom',
])
    @if ($backgroundStyle)
        style="{{ $backgroundStyle }}"
    @endif
>
    <div @class(['page-block__inner', 'page-block__inner--text-' . $contentWidth, 'page-cta'])>
        @if ($hasTextContent)
            <div>
                @if ($hasEyebrow)
                    <p class="page-block__eyebrow">{!! \App\Support\SiteVariables::renderText($data['eyebrow'], $settings ?? null) !!}</p>
                @endif

                @if ($hasHeading)
                    <h2>{!! \App\Support\SiteVariables::renderText($data['heading'], $settings ?? null) !!}</h2>
                @endif

                @if ($hasBody)
                    <div class="page-rich-text page-cta__body">
                        @if ($bodyHasHtml)
                            {!! \App\Support\RichContent::render($body) !!}
                        @else
                            <p>{!! \App\Support\SiteVariables::renderTextWithLineBreaks($body, $settings ?? null) !!}</p>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        @if (filled($data['button_label'] ?? null) && filled($data['button_url'] ?? null))
            <a class="page-block__button" href="{{ $data['button_url'] }}"{!! \App\Support\LinkAttributes::externalAttributes($data['button_url']) !!}>{!! \App\Support\SiteVariables::renderText($data['button_label'], $settings ?? null) !!}</a>
        @endif
    </div>
</section>

@php
    $code = $data['code'] ?? null;
    $contentWidth = match ($data['content_width'] ?? 'medium') {
        'small' => 'small',
        'wide', 'large' => 'wide',
        'full' => 'full',
        'none' => 'none',
        default => 'medium',
    };
    $background = \App\Support\SiteDesignPalette::backgroundKey($data['background'] ?? 'white');
    $backgroundStyle = \App\Support\SiteDesignPalette::pageBlockStyle($background);
@endphp

@if (filled($code))
    @if ($contentWidth === 'none')
        {!! $code !!}
    @else
        <section @class(['page-block', 'page-block--code', 'page-block--bg-' . $background])
            @if ($backgroundStyle)
                style="{{ $backgroundStyle }}"
            @endif
        >
            <div @class([
                'page-block__inner',
                'page-block__inner--text-' . $contentWidth => $contentWidth !== 'full',
                'page-block__inner--full' => $contentWidth === 'full',
                'page-code-block',
            ])>
                @if (filled($data['eyebrow'] ?? null))
                    <p class="page-block__eyebrow">{!! \App\Support\SiteVariables::renderText($data['eyebrow'], $settings ?? null) !!}</p>
                @endif

                @if (filled($data['heading'] ?? null))
                    <h2>{!! \App\Support\SiteVariables::renderText($data['heading'], $settings ?? null) !!}</h2>
                @endif

                {!! $code !!}
            </div>
        </section>
    @endif
@endif

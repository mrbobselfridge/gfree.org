@php
    $background = \App\Support\SiteDesignPalette::backgroundKey($data['background'] ?? 'white');
    $backgroundStyle = \App\Support\SiteDesignPalette::pageBlockStyle($background);
    $contentWidth = match ($data['content_width'] ?? 'wide') {
        'small' => 'small',
        'medium', 'normal' => 'medium',
        default => 'wide',
    };
@endphp

<section @class(['page-block', 'page-block--process-steps', 'page-block--bg-' . $background])
    aria-label="{{ $data['heading'] ?? 'Process steps' }}"
    @if ($backgroundStyle)
        style="{{ $backgroundStyle }}"
    @endif
>
    <div @class(['page-block__inner', 'page-block__inner--text-' . $contentWidth, 'page-process'])>
        <div class="page-process__intro">
            @if (filled($data['eyebrow'] ?? null))
                <p class="page-block__eyebrow">{!! \App\Support\SiteVariables::renderText($data['eyebrow'], $settings ?? null) !!}</p>
            @endif

            @if (filled($data['heading'] ?? null))
                <h2>{!! \App\Support\SiteVariables::renderText($data['heading'], $settings ?? null) !!}</h2>
            @endif
        </div>

        <div class="page-process__steps">
            @foreach (($data['steps'] ?? []) as $step)
                <article>
                    <strong>{!! \App\Support\SiteVariables::renderText($step['title'] ?? '', $settings ?? null) !!}</strong>

                    @if (filled($step['summary'] ?? null))
                        <span>{!! \App\Support\SiteVariables::renderText($step['summary'], $settings ?? null) !!}</span>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>

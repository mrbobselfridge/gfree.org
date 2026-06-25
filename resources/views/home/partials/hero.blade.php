@php
    $heroAutoRotate = (bool) ($heroBannersAutoRotate ?? false);
    $heroRotationDelayMilliseconds = max(1000, (int) ($heroBannersRotationDelayMilliseconds ?? 20000));
    $heroFadeDurationMilliseconds = max(1000, (int) ($heroBannersFadeDurationMilliseconds ?? 3000));
    $primaryLabel = $hero['primary_label'] ?? null;
    $secondaryLabel = $hero['secondary_label'] ?? null;
@endphp

<section
    class="concept-hero"
    data-hero-carousel
    @if ($heroAutoRotate)
        data-hero-auto
        data-hero-interval="{{ $heroRotationDelayMilliseconds }}"
        data-hero-fade-duration="{{ $heroFadeDurationMilliseconds }}"
    @endif
>
    <div class="concept-hero__image" data-hero-image style="background-image: url('{{ $hero['image_url'] }}')"></div>

    <div class="concept-hero__content">
        <p class="concept-eyebrow" data-hero-eyebrow>{!! $hero['eyebrow_html'] ?? \App\Support\SiteVariables::renderText($hero['eyebrow'] ?? '', $settings ?? null) !!}</p>
        <h1 data-hero-title>{!! $hero['title_html'] ?? \App\Support\SiteVariables::renderText($hero['title'] ?? '', $settings ?? null) !!}</h1>
        <div class="concept-hero__subtitle" data-hero-subtitle @if (blank($hero['subtitle'])) hidden @endif>{!! $hero['subtitle_html'] ?? \App\Support\RichContent::renderTextarea($hero['subtitle'] ?? '', $settings ?? null) !!}</div>

        <div class="concept-actions">
            <a
                href="{{ $hero['primary_url'] }}"
                class="concept-button concept-button--primary"
                data-hero-primary
                @if (\App\Support\LinkAttributes::isExternal($hero['primary_url'] ?? null)) target="_blank" rel="noopener noreferrer" @endif
                @if (blank($primaryLabel)) hidden @endif
            >
                {!! $hero['primary_label_html'] ?? \App\Support\SiteVariables::renderText($primaryLabel, $settings ?? null) !!}
            </a>

            <a
                href="{{ $hero['secondary_url'] }}"
                class="concept-button concept-button--secondary"
                data-hero-secondary
                @if (\App\Support\LinkAttributes::isExternal($hero['secondary_url'] ?? null)) target="_blank" rel="noopener noreferrer" @endif
                @if (blank($secondaryLabel)) hidden @endif
            >
                {!! $hero['secondary_label_html'] ?? \App\Support\SiteVariables::renderText($secondaryLabel, $settings ?? null) !!}
            </a>
        </div>

        @if ($heroSlides->count() > 1)
            <div class="concept-hero__controls" aria-label="Homepage banner controls">
                <button type="button" data-hero-previous aria-label="Previous homepage banner">
                    <span aria-hidden="true">&larr;</span>
                </button>
                @if ($heroAutoRotate)
                    <button type="button" data-hero-pause aria-label="Pause homepage banner rotation" aria-pressed="false">
                        <span data-hero-pause-label>Pause</span>
                    </button>
                @endif
                <button type="button" data-hero-next aria-label="Next homepage banner">
                    <span aria-hidden="true">&rarr;</span>
                </button>
            </div>
        @endif
    </div>

    <script type="application/json" data-hero-slides>
        @json($heroSlides)
    </script>
</section>

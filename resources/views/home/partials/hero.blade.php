<section class="concept-hero" data-hero-carousel>
    <div class="concept-hero__image" data-hero-image style="background-image: url('{{ $hero['image_url'] }}')"></div>

    <div class="concept-hero__content">
        <p class="concept-eyebrow" data-hero-eyebrow>{{ $hero['eyebrow'] }}</p>
        <h1 data-hero-title>{{ $hero['title'] }}</h1>
        <p data-hero-subtitle @if (blank($hero['subtitle'])) hidden @endif>{{ $hero['subtitle'] }}</p>

        <div class="concept-actions">
            <a href="{{ $hero['primary_url'] }}" class="concept-button concept-button--primary" data-hero-primary>{{ $hero['primary_label'] }}</a>
            <a href="{{ $hero['secondary_url'] }}" class="concept-button concept-button--secondary" data-hero-secondary>{{ $hero['secondary_label'] }}</a>
        </div>

        @if ($heroSlides->count() > 1)
            <div class="concept-hero__controls" aria-label="Homepage banner controls">
                <button type="button" data-hero-previous aria-label="Previous homepage banner">
                    <span aria-hidden="true">&larr;</span>
                </button>
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

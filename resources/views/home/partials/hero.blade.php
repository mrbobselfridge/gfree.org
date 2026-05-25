<section class="concept-hero">
    <div class="concept-hero__image" style="background-image: url('{{ $hero['image_url'] }}')"></div>

    <div class="concept-hero__content">
        <p class="concept-eyebrow">{{ $hero['eyebrow'] }}</p>
        <h1>{{ $hero['title'] }}</h1>
        <p>{{ $hero['subtitle'] }}</p>

        <div class="concept-actions">
            <a href="{{ $hero['primary_url'] }}" class="concept-button concept-button--primary">{{ $hero['primary_label'] }}</a>
            <a href="{{ $hero['secondary_url'] }}" class="concept-button concept-button--secondary">{{ $hero['secondary_label'] }}</a>
        </div>
    </div>
</section>

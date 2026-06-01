<section class="concept-feature">
    <div class="concept-feature__media"></div>
    <div>
        <p class="concept-eyebrow">{{ $feature['eyebrow'] }}</p>
        <h2>{{ $feature['title'] }}</h2>
        <p>{{ $feature['body'] }}</p>
        <a href="{{ $feature['url'] }}" class="concept-text-link"{!! \App\Support\LinkAttributes::externalAttributes($feature['url'] ?? null) !!}>{{ $feature['label'] }}</a>
    </div>
</section>

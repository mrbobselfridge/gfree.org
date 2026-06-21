<section class="concept-feature">
    <div class="concept-feature__media"></div>
    <div>
        <p class="concept-eyebrow">{!! \App\Support\SiteVariables::renderText($feature['eyebrow'], $settings ?? null) !!}</p>
        <h2>{!! \App\Support\SiteVariables::renderText($feature['title'], $settings ?? null) !!}</h2>
        <p>{!! \App\Support\SiteVariables::renderText($feature['body'], $settings ?? null) !!}</p>
        <a href="{{ $feature['url'] }}" class="concept-text-link"{!! \App\Support\LinkAttributes::externalAttributes($feature['url'] ?? null) !!}>{!! \App\Support\SiteVariables::renderText($feature['label'], $settings ?? null) !!}</a>
    </div>
</section>

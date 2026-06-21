<section class="concept-section concept-section--intro">
    <div>
        <p class="concept-eyebrow">{!! \App\Support\SiteVariables::renderText($intro['eyebrow'], $settings ?? null) !!}</p>
        <h2>{!! \App\Support\SiteVariables::renderText($intro['title'], $settings ?? null) !!}</h2>
    </div>
    <p>{!! \App\Support\SiteVariables::renderText($intro['body'], $settings ?? null) !!}</p>
</section>

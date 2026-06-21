<section class="concept-process" aria-label="Serving process">
    <div class="concept-process__intro">
        <p class="concept-eyebrow">{!! \App\Support\SiteVariables::renderText($process['eyebrow'], $settings ?? null) !!}</p>
        <h2>{!! \App\Support\SiteVariables::renderText($process['title'], $settings ?? null) !!}</h2>
    </div>

    <div class="concept-process__steps">
        @foreach ($process['steps'] as $step)
            <article>
                <strong>{!! \App\Support\SiteVariables::renderText($step['title'], $settings ?? null) !!}</strong>
                <span>{!! \App\Support\SiteVariables::renderText($step['summary'], $settings ?? null) !!}</span>
            </article>
        @endforeach
    </div>
</section>

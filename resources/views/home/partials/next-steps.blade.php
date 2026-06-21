<section class="concept-card-row" aria-label="Next steps">
    @foreach ($nextSteps as $step)
        <article>
            <span>{!! \App\Support\SiteVariables::renderText($step['number'], $settings ?? null) !!}</span>
            <h3>{!! \App\Support\SiteVariables::renderText($step['title'], $settings ?? null) !!}</h3>
            <p>{!! \App\Support\SiteVariables::renderText($step['summary'], $settings ?? null) !!}</p>
            <a href="{{ $step['url'] }}" aria-label="Open {{ $step['title'] }}"{!! \App\Support\LinkAttributes::externalAttributes($step['url'] ?? null) !!}></a>
        </article>
    @endforeach
</section>

<section class="concept-card-row" aria-label="Next steps">
    @foreach ($nextSteps as $step)
        <article>
            <span>{{ $step['number'] }}</span>
            <h3>{{ $step['title'] }}</h3>
            <p>{{ $step['summary'] }}</p>
            <a href="{{ $step['url'] }}" aria-label="Open {{ $step['title'] }}"{!! \App\Support\LinkAttributes::externalAttributes($step['url'] ?? null) !!}></a>
        </article>
    @endforeach
</section>

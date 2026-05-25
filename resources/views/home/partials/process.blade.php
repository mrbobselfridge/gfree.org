<section class="concept-process" aria-label="Serving process">
    <div class="concept-process__intro">
        <p class="concept-eyebrow">{{ $process['eyebrow'] }}</p>
        <h2>{{ $process['title'] }}</h2>
    </div>

    <div class="concept-process__steps">
        @foreach ($process['steps'] as $step)
            <article>
                <strong>{{ $step['title'] }}</strong>
                <span>{{ $step['summary'] }}</span>
            </article>
        @endforeach
    </div>
</section>

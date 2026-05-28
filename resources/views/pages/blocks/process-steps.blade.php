@php($background = $data['background'] ?? 'white')

<section @class(['page-block', 'page-block--process-steps', 'page-block--bg-' . $background]) aria-label="{{ $data['heading'] ?? 'Process steps' }}">
    <div class="page-block__inner page-process">
        <div class="page-process__intro">
            @if (filled($data['eyebrow'] ?? null))
                <p class="page-block__eyebrow">{{ $data['eyebrow'] }}</p>
            @endif

            @if (filled($data['heading'] ?? null))
                <h2>{{ $data['heading'] }}</h2>
            @endif
        </div>

        <div class="page-process__steps">
            @foreach (($data['steps'] ?? []) as $step)
                <article>
                    <strong>{{ $step['title'] ?? '' }}</strong>

                    @if (filled($step['summary'] ?? null))
                        <span>{{ $step['summary'] }}</span>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>

@php($background = $data['background'] ?? 'white')

<section @class(['page-block', 'page-block--link-cards', 'page-block--bg-' . $background])>
    <div class="page-block__inner">
        @if (filled($data['eyebrow'] ?? null))
            <p class="page-block__eyebrow">{{ $data['eyebrow'] }}</p>
        @endif

        @if (filled($data['heading'] ?? null))
            <h2>{{ $data['heading'] }}</h2>
        @endif

        <div class="page-link-cards">
            @foreach (($data['cards'] ?? []) as $card)
                <a href="{{ $card['url'] ?? '#' }}">
                    <h3>{{ $card['title'] ?? '' }}</h3>

                    @if (filled($card['summary'] ?? null))
                        <p>{{ $card['summary'] }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</section>

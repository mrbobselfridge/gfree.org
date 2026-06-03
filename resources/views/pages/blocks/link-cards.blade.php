@php
    $background = $data['background'] ?? 'white';
    $cards = $data['cards'] ?? [];
@endphp

<section @class(['page-block', 'page-block--link-cards', 'page-block--bg-' . $background])>
    <div class="page-block__inner">
        @if (filled($data['eyebrow'] ?? null))
            <p class="page-block__eyebrow">{{ $data['eyebrow'] }}</p>
        @endif

        @if (filled($data['heading'] ?? null))
            <h2>{{ $data['heading'] }}</h2>
        @endif

        <div class="page-link-cards">
            @foreach ($cards as $card)
                @php($url = trim((string) ($card['url'] ?? '')))

                @if (filled($url))
                    <a class="page-link-card" href="{{ $url }}"{!! \App\Support\LinkAttributes::externalAttributes($url) !!}>
                        <h3>{{ $card['title'] ?? '' }}</h3>

                        @if (filled($card['summary'] ?? null))
                            <p>{{ $card['summary'] }}</p>
                        @endif
                    </a>
                @else
                    <div class="page-link-card">
                        <h3>{{ $card['title'] ?? '' }}</h3>

                        @if (filled($card['summary'] ?? null))
                            <p>{{ $card['summary'] }}</p>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</section>

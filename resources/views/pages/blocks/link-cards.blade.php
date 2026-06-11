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
                @php
                    $url = trim((string) ($card['url'] ?? ''));
                    $type = \App\Support\LinkCard::normalizeType($card['type'] ?? null, $url);
                    $cardKey = \App\Support\LinkCard::sanitizedKey($card['key'] ?? 'block-'.(($blockIndex ?? 0) + 1).'-card-'.$loop->iteration);
                    $flipId = \App\Support\LinkCard::flipId($cardKey);
                    $widgetId = \App\Support\LinkCard::widgetId($cardKey);
                @endphp

                @if ($type === \App\Support\LinkCard::TYPE_LINK_SAME && \App\Support\LinkCard::isSafeHref($url))
                    <a class="page-link-card" href="{{ $url }}">
                        <h3>{{ $card['title'] ?? '' }}</h3>

                        @if (filled($card['summary'] ?? null))
                            <p>{{ $card['summary'] }}</p>
                        @endif
                    </a>
                @elseif ($type === \App\Support\LinkCard::TYPE_LINK_NEW && \App\Support\LinkCard::isSafeHref($url))
                    <a class="page-link-card" href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                        <h3>{{ $card['title'] ?? '' }}</h3>

                        @if (filled($card['summary'] ?? null))
                            <p>{{ $card['summary'] }}</p>
                        @endif
                    </a>
                @elseif ($type === \App\Support\LinkCard::TYPE_FLIP_HTML)
                    <button
                        type="button"
                        id="{{ $flipId }}"
                        class="page-link-card page-link-card--flip"
                        aria-pressed="false"
                        data-card-flip
                    >
                        <span class="page-link-card__flip-inner">
                            <span class="page-link-card__face page-link-card__face--front">
                                <h3>{{ $card['title'] ?? '' }}</h3>

                                @if (filled($card['summary'] ?? null))
                                    <p>{{ $card['summary'] }}</p>
                                @endif
                            </span>

                            <span class="page-link-card__face page-link-card__face--back">
                                {!! $card['html'] ?? '' !!}
                            </span>
                        </span>
                    </button>
                @elseif ($type === \App\Support\LinkCard::TYPE_JAVASCRIPT_WIDGET)
                    <div class="page-link-card page-link-card--widget">
                        <h3>{{ $card['title'] ?? '' }}</h3>

                        @if (filled($card['summary'] ?? null))
                            <p>{{ $card['summary'] }}</p>
                        @endif

                        <div id="{{ $widgetId }}" class="page-link-card__widget"></div>

                        @if (filled($card['javascript'] ?? null))
                            <script>
                                {!! $card['javascript'] !!}
                            </script>
                        @endif
                    </div>
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

@once
    <script>
        document.addEventListener('click', (event) => {
            const card = event.target.closest('[data-card-flip]');

            if (!card) {
                return;
            }

            const isFlipped = card.classList.toggle('is-flipped');
            card.setAttribute('aria-pressed', isFlipped ? 'true' : 'false');
        });
    </script>
@endonce

@php
    $background = \App\Support\SiteDesignPalette::backgroundKey($data['background'] ?? 'white');
    $backgroundStyle = \App\Support\SiteDesignPalette::pageBlockStyle($background);
    $backgroundTarget = ($data['background_target'] ?? 'page') === 'item' ? 'item' : 'page';
    $sectionBackground = $backgroundTarget === 'page' ? $background : 'white';
    $contentWidth = match ($data['content_width'] ?? 'wide') {
        'small' => 'small',
        'medium', 'normal' => 'medium',
        default => 'wide',
    };
    $cards = $data['cards'] ?? [];
    $hasEyebrow = \App\Support\RichContent::hasRenderableContent($data['eyebrow'] ?? null);
    $hasHeading = \App\Support\RichContent::hasRenderableContent($data['heading'] ?? null);
@endphp

<section
    @class([
        'page-block',
        'page-block--link-cards',
        'page-block--link-cards-target-' . $backgroundTarget,
        'page-block--bg-' . $sectionBackground,
    ])
    @if ($backgroundTarget === 'page' && $backgroundStyle)
        style="{{ $backgroundStyle }}"
    @endif
>
    <div @class(['page-block__inner', 'page-block__inner--text-' . $contentWidth])>
        @if ($hasEyebrow)
            <p class="page-block__eyebrow">{!! \App\Support\SiteVariables::renderText($data['eyebrow'], $settings ?? null) !!}</p>
        @endif

        @if ($hasHeading)
            <h2>{!! \App\Support\SiteVariables::renderText($data['heading'], $settings ?? null) !!}</h2>
        @endif

        <div
            @class([
                'page-link-cards',
                'page-link-cards--target-' . $backgroundTarget,
                'page-block--bg-' . $background => $backgroundTarget === 'item',
            ])
            @if ($backgroundTarget === 'item' && $backgroundStyle)
                style="{{ $backgroundStyle }}"
            @endif
        >
            @foreach ($cards as $card)
                @php
                    $url = trim((string) ($card['url'] ?? ''));
                    $type = \App\Support\LinkCard::normalizeType($card['type'] ?? null, $url);
                    $cardKey = \App\Support\LinkCard::sanitizedKey($card['key'] ?? 'block-'.(($blockIndex ?? 0) + 1).'-card-'.$loop->iteration);
                    $flipId = \App\Support\LinkCard::flipId($cardKey);
                    $widgetId = \App\Support\LinkCard::widgetId($cardKey);
                    $imageUrl = \App\Support\ContentBlocks::imageUrl($card['image_path'] ?? null);
                    $imageFit = \App\Support\LinkCard::normalizeImageFit($card['image_fit'] ?? null);
                    $imageFocus = \App\Support\LinkCard::imageFocusPosition(
                        $card['image_focus'] ?? null,
                        $card['image_focus_x'] ?? null,
                        $card['image_focus_y'] ?? null,
                    );
                    $imageZoom = \App\Support\LinkCard::normalizeImageZoom($card['image_zoom'] ?? null);
                    $hasSafeDestination = \App\Support\LinkCard::isSafeHref($url);
                    $destinationLabel = trim(strip_tags((string) \App\Support\SiteVariables::renderText($card['title'] ?? 'card', $settings ?? null)));
                @endphp

                @if ($type === \App\Support\LinkCard::TYPE_LINK_SAME && \App\Support\LinkCard::isSafeHref($url))
                    <a class="page-link-card" href="{{ $url }}">
                        <h3>{!! \App\Support\SiteVariables::renderText($card['title'] ?? '', $settings ?? null) !!}</h3>

                        @if (filled($card['summary'] ?? null))
                            <div class="page-link-card__summary">{!! \App\Support\RichContent::renderTextarea($card['summary'], $settings ?? null) !!}</div>
                        @endif
                    </a>
                @elseif ($type === \App\Support\LinkCard::TYPE_LINK_NEW && \App\Support\LinkCard::isSafeHref($url))
                    <a class="page-link-card" href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                        <h3>{!! \App\Support\SiteVariables::renderText($card['title'] ?? '', $settings ?? null) !!}</h3>

                        @if (filled($card['summary'] ?? null))
                            <div class="page-link-card__summary">{!! \App\Support\RichContent::renderTextarea($card['summary'], $settings ?? null) !!}</div>
                        @endif
                    </a>
                @elseif ($type === \App\Support\LinkCard::TYPE_FLIP_HTML)
                    <div
                        role="button"
                        tabindex="0"
                        id="{{ $flipId }}"
                        class="page-link-card page-link-card--flip"
                        aria-pressed="false"
                        data-card-flip
                    >
                        <div class="page-link-card__flip-inner">
                            <div class="page-link-card__face page-link-card__face--front">
                                <h3>{!! \App\Support\SiteVariables::renderText($card['title'] ?? '', $settings ?? null) !!}</h3>

                                @if (filled($card['summary'] ?? null))
                                    <div class="page-link-card__summary">{!! \App\Support\RichContent::renderTextarea($card['summary'], $settings ?? null) !!}</div>
                                @endif
                            </div>

                            <div @class([
                                'page-link-card__face',
                                'page-link-card__face--back',
                                'page-link-card__face--has-cta' => $hasSafeDestination,
                            ])>
                                {!! \App\Support\SiteVariables::renderHtml($card['html'] ?? '', $settings ?? null) !!}

                                @if ($hasSafeDestination)
                                    <a
                                        href="{{ $url }}"
                                        class="page-link-card__cta"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="More about {{ $destinationLabel }}"
                                    >
                                        More
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif ($type === \App\Support\LinkCard::TYPE_FLIP_IMAGE && filled($imageUrl))
                    <div
                        role="button"
                        tabindex="0"
                        id="{{ $flipId }}"
                        class="page-link-card page-link-card--flip"
                        aria-pressed="false"
                        data-card-flip
                    >
                        <div class="page-link-card__flip-inner">
                            <div class="page-link-card__face page-link-card__face--front">
                                <h3>{!! \App\Support\SiteVariables::renderText($card['title'] ?? '', $settings ?? null) !!}</h3>

                                @if (filled($card['summary'] ?? null))
                                    <div class="page-link-card__summary">{!! \App\Support\RichContent::renderTextarea($card['summary'], $settings ?? null) !!}</div>
                                @endif
                            </div>

                            <div class="page-link-card__face page-link-card__face--back page-link-card__face--image">
                                <img
                                    src="{{ $imageUrl }}"
                                    alt="{{ $card['image_alt'] ?? ($card['title'] ?? '') }}"
                                    class="page-link-card__flip-image page-link-card__flip-image--{{ $imageFit }}"
                                    style="object-position: {{ $imageFocus }}; transform: scale({{ $imageZoom / 100 }}); transform-origin: {{ $imageFocus }};"
                                >

                                @if ($hasSafeDestination)
                                    <a
                                        href="{{ $url }}"
                                        class="page-link-card__cta"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="More about {{ $destinationLabel }}"
                                    >
                                        More
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif ($type === \App\Support\LinkCard::TYPE_JAVASCRIPT_WIDGET)
                    <div class="page-link-card page-link-card--widget">
                        <h3>{!! \App\Support\SiteVariables::renderText($card['title'] ?? '', $settings ?? null) !!}</h3>

                        @if (filled($card['summary'] ?? null))
                            <div class="page-link-card__summary">{!! \App\Support\RichContent::renderTextarea($card['summary'], $settings ?? null) !!}</div>
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
                        <h3>{!! \App\Support\SiteVariables::renderText($card['title'] ?? '', $settings ?? null) !!}</h3>

                        @if (filled($card['summary'] ?? null))
                            <div class="page-link-card__summary">{!! \App\Support\RichContent::renderTextarea($card['summary'], $settings ?? null) !!}</div>
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

            if (event.target.closest('a')) {
                return;
            }

            if (!card) {
                return;
            }

            const isFlipped = card.classList.toggle('is-flipped');
            card.setAttribute('aria-pressed', isFlipped ? 'true' : 'false');
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            const card = event.target.closest('[data-card-flip]');

            if (!card) {
                return;
            }

            event.preventDefault();
            card.click();
        });
    </script>
@endonce

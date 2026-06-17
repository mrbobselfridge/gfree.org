@php($items = collect($data['items'] ?? []))
@php($background = $data['background'] ?? 'white')
@php($layout = $data['layout'] ?? \App\Support\ContentBlocks::RELATED_CONTENT_LAYOUT_CARD_GRID)
@php($hasHeaderText = filled($data['heading'] ?? null) || filled($data['intro'] ?? null))

@if ($items->isNotEmpty())
    <section @class([
        'concept-updates',
        'concept-updates--bar',
        'concept-updates--child-info-cards',
        'concept-updates--child-info-cards-has-heading' => filled($data['heading'] ?? null),
        'concept-updates--layout-' . $layout,
        'concept-updates--bg-' . $background,
    ])>
        <div class="concept-updates__inner">
            @if ($hasHeaderText)
                <div class="concept-updates__header">
                    <div>
                        @if (filled($data['heading'] ?? null))
                            <h2>{{ $data['heading'] }}</h2>
                        @endif

                        @if (filled($data['intro'] ?? null))
                            <span>{{ $data['intro'] }}</span>
                        @endif
                    </div>
                </div>
            @endif

            @switch($layout)
                @case(\App\Support\ContentBlocks::RELATED_CONTENT_LAYOUT_CARD_CAROUSEL)
                    @include('pages.partials.related-content-carousel', [
                        'items' => $items,
                        'initialCount' => $data['initial_item_limit'] ?? $data['item_limit'] ?? 3,
                    ])
                    @break

                @case(\App\Support\ContentBlocks::RELATED_CONTENT_LAYOUT_BULLET_LIST)
                    @include('pages.partials.related-content-list', [
                        'items' => $items,
                        'initialCount' => $data['initial_item_limit'] ?? $data['item_limit'] ?? $items->count(),
                        'hasLoadMore' => $data['has_more'] ?? false,
                    ])
                    @break

                @default
                    @include('pages.partials.related-content-grid', [
                        'items' => $items,
                        'initialCount' => $data['initial_item_limit'] ?? $data['item_limit'] ?? $items->count(),
                        'hasLoadMore' => $data['has_more'] ?? false,
                    ])
            @endswitch
        </div>
    </section>
@endif

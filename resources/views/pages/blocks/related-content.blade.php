@php($items = collect($data['items'] ?? []))
@php($background = $data['background'] ?? 'white')
@php($hasHeaderText = filled($data['heading'] ?? null) || filled($data['intro'] ?? null))

@if ($items->isNotEmpty())
    <section @class([
        'concept-updates',
        'concept-updates--bar',
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

            @include('pages.partials.related-content-grid', [
                'items' => $items,
                'initialCount' => $data['initial_item_limit'] ?? $data['item_limit'] ?? $items->count(),
                'hasLoadMore' => $data['has_more'] ?? false,
            ])
        </div>
    </section>
@endif

@php($items = collect($data['items'] ?? []))
@php($background = $data['background'] ?? 'white')
@php($hasHeaderText = filled($data['heading'] ?? null) || filled($data['intro'] ?? null))
@php($hasViewMoreLink = filled($data['link_label'] ?? null) && filled($data['view_more_url'] ?? null))

@if ($items->isNotEmpty())
    <section @class([
        'concept-updates',
        'concept-updates--bar',
        'concept-updates--bg-' . $background,
    ])>
        <div class="concept-updates__inner">
            @if ($hasHeaderText || $hasViewMoreLink)
                <div class="concept-updates__header">
                    <div>
                        @if (filled($data['heading'] ?? null))
                            <h2>{{ $data['heading'] }}</h2>
                        @endif

                        @if (filled($data['intro'] ?? null))
                            <span>{{ $data['intro'] }}</span>
                        @endif
                    </div>

                    @if ($hasViewMoreLink)
                        <a href="{{ $data['view_more_url'] }}"{!! \App\Support\LinkAttributes::externalAttributes($data['view_more_url']) !!}>{{ $data['link_label'] }}</a>
                    @endif
                </div>
            @endif

            @include('pages.partials.related-content-grid', ['items' => $items])
        </div>
    </section>
@endif

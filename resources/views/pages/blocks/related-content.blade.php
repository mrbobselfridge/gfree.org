@php($items = collect($data['items'] ?? []))
@php($background = $data['background'] ?? 'white')

@if ($items->isNotEmpty())
    <section @class([
        'concept-updates',
        'concept-updates--bar',
        'concept-updates--bg-' . $background,
    ])>
        <div class="concept-updates__inner">
            <div class="concept-updates__header">
                <div>
                    <h2>{{ $data['heading'] ?? 'Child Cards' }}</h2>

                    @if (filled($data['intro'] ?? null))
                        <span>{{ $data['intro'] }}</span>
                    @endif
                </div>

                @if (filled($data['link_label'] ?? null) && filled($data['view_more_url'] ?? null))
                    <a href="{{ $data['view_more_url'] }}"{!! \App\Support\LinkAttributes::externalAttributes($data['view_more_url']) !!}>{{ $data['link_label'] }}</a>
                @endif
            </div>

            @include('pages.partials.related-content-grid', ['items' => $items])
        </div>
    </section>
@endif

@php($items = collect($items ?? []))
@php($initialCount = max(1, (int) ($initialCount ?? $items->count())))
@php($hasLoadMore = (bool) ($hasLoadMore ?? false))
@php($searchEnabled = (bool) ($searchEnabled ?? false))

<div
    class="concept-updates__label-list"
    @if ($searchEnabled)
        data-related-search-listing
    @endif
    @if ($hasLoadMore)
        data-related-load-more
        data-related-page-size="{{ $initialCount }}"
    @endif
>
<ul class="concept-updates__bullet-list">
    @foreach ($items as $item)
        @php($isFile = data_get($item, 'kind') === 'file')
        @php($title = data_get($item, 'title'))
        @php($url = data_get($item, 'url'))
        @php($imageUrl = data_get($item, 'image_url'))
        @php($summary = filled(data_get($item, 'summary')) ? \Illuminate\Support\Str::limit((string) data_get($item, 'summary'), 180) : null)
        @php($message = trim(html_entity_decode(strip_tags((string) data_get($item, 'message')), ENT_QUOTES | ENT_HTML5, 'UTF-8')))
        @php($message = $message !== '' ? \Illuminate\Support\Str::limit($message, 120) : null)
        @php($isHidden = $hasLoadMore && $loop->iteration > $initialCount)

        <li
            class="concept-updates__bullet-item"
            @if ($searchEnabled)
                data-related-search-item
                data-related-search="{{ data_get($item, 'search_text') }}"
                data-related-initial-hidden="{{ $isHidden ? 'true' : 'false' }}"
            @endif
            @if ($isHidden)
                hidden
                data-related-load-more-item
            @endif
        >
            @if ($imageUrl)
                @if ($url)
                    <a class="concept-updates__bullet-media" href="{{ $url }}" aria-label="{{ $isFile ? 'Download ' : 'Open ' }}{{ $title }}"{!! \App\Support\LinkAttributes::externalAttributes($url) !!}>
                        <img src="{{ $imageUrl }}" alt="">
                    </a>
                @else
                    <div class="concept-updates__bullet-media">
                        <img src="{{ $imageUrl }}" alt="">
                    </div>
                @endif
            @endif

            <div class="concept-updates__bullet-body">
                @if ($url)
                    <a class="concept-updates__bullet-title" href="{{ $url }}" aria-label="{{ $isFile ? 'Download ' : 'Open ' }}{{ $title }}"{!! \App\Support\LinkAttributes::externalAttributes($url) !!}>{!! \App\Support\SiteVariables::renderText($title, $settings ?? null) !!}</a>
                @else
                    <strong class="concept-updates__bullet-title">{!! \App\Support\SiteVariables::renderText($title, $settings ?? null) !!}</strong>
                @endif

                @if (filled($summary))
                    <span class="concept-updates__bullet-summary">{!! \App\Support\SiteVariables::renderText($summary, $settings ?? null) !!}</span>
                @endif

                @if (filled(data_get($item, 'type')) || filled($message))
                    <div class="concept-updates__bullet-meta">
                        @if (filled(data_get($item, 'type')))
                            <span class="concept-updates__bullet-label">{!! \App\Support\SiteVariables::renderText(data_get($item, 'type'), $settings ?? null) !!}</span>
                        @endif

                        @if (filled(data_get($item, 'type')) && filled($message))
                            <span class="concept-updates__bullet-separator" aria-hidden="true">|</span>
                        @endif

                        @if (filled($message))
                            <span class="concept-updates__bullet-message">{!! \App\Support\SiteVariables::renderText($message, $settings ?? null) !!}</span>
                        @endif
                    </div>
                @endif
            </div>
        </li>
    @endforeach
</ul>

    @if ($hasLoadMore)
        <button class="concept-updates__load-more" type="button" data-related-load-more-trigger>
            Load more
        </button>
    @endif
</div>

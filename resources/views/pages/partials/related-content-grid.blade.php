@php($items = collect($items ?? []))
@php($initialCount = max(1, (int) ($initialCount ?? $items->count())))
@php($hasLoadMore = (bool) ($hasLoadMore ?? false))
@php($searchEnabled = (bool) ($searchEnabled ?? false))

<div
    class="concept-updates__listing"
    @if ($searchEnabled)
        data-related-search-listing
    @endif
    @if ($hasLoadMore)
        data-related-load-more
        data-related-page-size="{{ $initialCount }}"
    @endif
>
    <div class="concept-updates__grid">
        @foreach ($items as $item)
            @include('pages.partials.related-content-card', [
                'item' => $item,
                'index' => $loop->iteration,
                'isHidden' => $hasLoadMore && $loop->iteration > $initialCount,
                'modalIdPrefix' => 'related-grid-file-content',
                'searchEnabled' => $searchEnabled,
            ])
        @endforeach
    </div>

    @if ($hasLoadMore)
        <button class="concept-updates__load-more" type="button" data-related-load-more-trigger>
            Load more
        </button>
    @endif
</div>

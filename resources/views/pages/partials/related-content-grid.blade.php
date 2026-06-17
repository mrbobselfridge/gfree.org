@php($items = collect($items ?? []))
@php($initialCount = max(1, (int) ($initialCount ?? $items->count())))
@php($hasLoadMore = (bool) ($hasLoadMore ?? false))

<div
    class="concept-updates__listing"
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
            ])
        @endforeach
    </div>

    @if ($hasLoadMore)
        <button class="concept-updates__load-more" type="button" data-related-load-more-trigger>
            Load more
        </button>
    @endif
</div>

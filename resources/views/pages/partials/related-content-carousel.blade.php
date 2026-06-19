@php($items = collect($items ?? []))
@php($visibleCount = max(1, min(3, (int) ($initialCount ?? 3))))
@php($searchEnabled = (bool) ($searchEnabled ?? false))
@php($isAuto = (bool) ($isAuto ?? false))
@php($autoIntervalMilliseconds = max(1000, (int) ($autoIntervalMilliseconds ?? 10000)))
@php($hasControls = $items->count() > $visibleCount)
@php($cardBasis = match ($visibleCount) {
    1 => '100%',
    2 => 'calc((100% - 14px) / 2)',
    default => 'calc((100% - 28px) / 3)',
})

<div
    class="concept-updates__carousel"
    data-related-carousel
    @if ($searchEnabled)
        data-related-search-listing
    @endif
    @if ($isAuto)
        data-related-carousel-auto
        data-related-carousel-interval="{{ $autoIntervalMilliseconds }}"
    @endif
    data-related-carousel-visible-count="{{ $visibleCount }}"
    style="--related-carousel-card-basis: {{ $cardBasis }}"
>
    @if ($hasControls && $items->count() > 1)
        <div class="concept-updates__carousel-controls" aria-label="Child listing carousel controls" hidden>
            <button class="concept-updates__carousel-button" type="button" data-related-carousel-previous aria-label="Show previous child listing item">
                <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                    <path d="M19 12H5"></path>
                    <path d="m12 19-7-7 7-7"></path>
                </svg>
            </button>
            <button class="concept-updates__carousel-button" type="button" data-related-carousel-next aria-label="Show next child listing item">
                <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                    <path d="M5 12h14"></path>
                    <path d="m12 5 7 7-7 7"></path>
                </svg>
            </button>
        </div>
    @endif

    <div class="concept-updates__carousel-viewport" data-related-carousel-viewport>
        <div class="concept-updates__carousel-track" data-related-carousel-track>
            @foreach ($items as $item)
                @include('pages.partials.related-content-card', [
                    'item' => $item,
                    'index' => $loop->iteration,
                    'modalIdPrefix' => 'related-carousel-file-content',
                    'searchEnabled' => $searchEnabled,
                ])
            @endforeach
        </div>
    </div>
</div>

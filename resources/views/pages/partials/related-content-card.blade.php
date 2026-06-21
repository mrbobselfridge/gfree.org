@php($isFile = data_get($item, 'kind') === 'file')
@php($hasMoreContent = $isFile && data_get($item, 'has_more_content') && filled(data_get($item, 'optional_content_html')))
@php($title = data_get($item, 'title'))
@php($url = data_get($item, 'url'))
@php($modalId = $hasMoreContent ? ($modalIdPrefix ?? 'related-file-content') . '-' . ($index ?? 1) : null)
@php($isHidden = (bool) ($isHidden ?? false))
@php($searchEnabled = (bool) ($searchEnabled ?? false))

<article
    @class([
        'concept-updates__card',
        'concept-updates__card--file' => $isFile,
    ])
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
    @if (data_get($item, 'image_url'))
        @if ($url)
            <a class="concept-updates__media-link" href="{{ $url }}" aria-label="{{ $isFile ? 'Download ' : 'Open ' }}{{ $title }}"{!! \App\Support\LinkAttributes::externalAttributes($url) !!}>
                <img src="{{ data_get($item, 'image_url') }}" alt="">
            </a>
        @else
            <img src="{{ data_get($item, 'image_url') }}" alt="">
        @endif
    @endif

    <div class="concept-updates__card-body">
        <p>{!! \App\Support\SiteVariables::renderText(data_get($item, 'type'), $settings ?? null) !!}</p>
        <h3>
            @if (! $isFile && $url)
                <a href="{{ $url }}" aria-label="Open {{ $title }}"{!! \App\Support\LinkAttributes::externalAttributes($url) !!}>{!! \App\Support\SiteVariables::renderText($title, $settings ?? null) !!}</a>
            @else
                {!! \App\Support\SiteVariables::renderText($title, $settings ?? null) !!}
            @endif
        </h3>

        @if (filled(data_get($item, 'summary')))
            <span>{!! \App\Support\SiteVariables::renderText(data_get($item, 'summary'), $settings ?? null) !!}</span>
        @endif

        @php($message = \App\Support\RichContent::nullable(data_get($item, 'message')))

        @if (! $isFile && filled($message))
            <div class="concept-updates__card-message">
                @if ($message !== strip_tags($message))
                    {!! \App\Support\RichContent::render($message) !!}
                @else
                    @foreach (preg_split('/\R{2,}/', $message) as $paragraph)
                        <p>{!! \App\Support\SiteVariables::renderTextWithLineBreaks($paragraph, $settings ?? null) !!}</p>
                    @endforeach
                @endif
            </div>
        @endif

        @if ($hasMoreContent)
            <button class="concept-updates__more-button" type="button" data-related-modal-open aria-controls="{{ $modalId }}">More</button>
        @endif
    </div>

    @if ($hasMoreContent)
        <dialog class="concept-updates__modal" id="{{ $modalId }}" data-related-modal>
            <div class="concept-updates__modal-panel">
                <div class="concept-updates__modal-header">
                    <div>
                        <p>{!! \App\Support\SiteVariables::renderText(data_get($item, 'type'), $settings ?? null) !!}</p>
                        <h2>{!! \App\Support\SiteVariables::renderText($title, $settings ?? null) !!}</h2>
                    </div>
                    <button class="concept-updates__modal-close" type="button" data-related-modal-close aria-label="Close {{ $title }} content">Close</button>
                </div>

                <div class="concept-updates__modal-body">
                    <div class="concept-updates__rich-text">
                        {!! \App\Support\SiteVariables::renderHtml(data_get($item, 'optional_content_html'), $settings ?? null) !!}
                    </div>
                </div>
            </div>
        </dialog>
    @endif
</article>

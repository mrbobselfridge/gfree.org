@php($isFile = data_get($item, 'kind') === 'file')
@php($hasMoreContent = $isFile && data_get($item, 'has_more_content') && filled(data_get($item, 'optional_content_html')))
@php($title = data_get($item, 'title'))
@php($url = data_get($item, 'url'))
@php($modalId = $hasMoreContent ? ($modalIdPrefix ?? 'related-file-content') . '-' . ($index ?? 1) : null)
@php($isHidden = (bool) ($isHidden ?? false))

<article
    @class([
        'concept-updates__card',
        'concept-updates__card--file' => $isFile,
    ])
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
        <p>{{ data_get($item, 'type') }}</p>
        <h3>
            @if (! $isFile && $url)
                <a href="{{ $url }}" aria-label="Open {{ $title }}"{!! \App\Support\LinkAttributes::externalAttributes($url) !!}>{{ $title }}</a>
            @else
                {{ $title }}
            @endif
        </h3>

        @if (filled(data_get($item, 'summary')))
            <span>{{ data_get($item, 'summary') }}</span>
        @endif

        @if (! $isFile && filled(data_get($item, 'message')))
            <div class="concept-updates__card-message">
                @php($message = trim((string) data_get($item, 'message')))

                @if ($message !== strip_tags($message))
                    {!! \App\Support\RichContent::render($message) !!}
                @else
                    @foreach (preg_split('/\R{2,}/', $message) as $paragraph)
                        <p>{!! nl2br(e($paragraph)) !!}</p>
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
                        <p>{{ data_get($item, 'type') }}</p>
                        <h2>{{ $title }}</h2>
                    </div>
                    <button class="concept-updates__modal-close" type="button" data-related-modal-close aria-label="Close {{ $title }} content">Close</button>
                </div>

                <div class="concept-updates__modal-body">
                    <div class="concept-updates__rich-text">
                        {!! data_get($item, 'optional_content_html') !!}
                    </div>
                </div>
            </div>
        </dialog>
    @endif
</article>

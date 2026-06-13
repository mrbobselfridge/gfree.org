@php($items = collect($items ?? []))

<div class="concept-updates__grid">
    @foreach ($items as $item)
        <article>
            @if (data_get($item, 'image_url'))
                <img src="{{ data_get($item, 'image_url') }}" alt="">
            @endif

            <div>
                <p>{{ data_get($item, 'type') }}</p>
                <h3>{{ data_get($item, 'title') }}</h3>

                @if (filled(data_get($item, 'summary')))
                    <span>{{ data_get($item, 'summary') }}</span>
                @endif

                <a href="{{ data_get($item, 'url') }}" aria-label="Open {{ data_get($item, 'title') }}"{!! \App\Support\LinkAttributes::externalAttributes(data_get($item, 'url')) !!}></a>
            </div>
        </article>
    @endforeach
</div>

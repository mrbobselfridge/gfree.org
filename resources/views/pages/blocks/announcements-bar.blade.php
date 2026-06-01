@php($updates = collect($data['updates'] ?? []))
@php($background = $data['background'] ?? 'white')

@if ($updates->isNotEmpty())
    <section @class([
        'concept-updates',
        'concept-updates--bar',
        'concept-updates--bg-' . $background,
    ])>
        <div class="concept-updates__inner">
            <div class="concept-updates__header">
                <h2>{{ $data['heading'] ?? 'Latest at gFree' }}</h2>

                @if (filled($data['link_label'] ?? null) && filled($data['link_url'] ?? null))
                    <a href="{{ $data['link_url'] }}"{!! \App\Support\LinkAttributes::externalAttributes($data['link_url']) !!}>{{ $data['link_label'] }}</a>
                @endif
            </div>

            <div class="concept-updates__grid">
                @foreach ($updates as $update)
                    <article>
                        @if (data_get($update, 'image_url'))
                            <img src="{{ data_get($update, 'image_url') }}" alt="">
                        @endif

                        <div>
                            <p>{{ $update['type'] }}</p>
                            <h3>{{ $update['title'] }}</h3>
                            <span>{{ $update['summary'] }}</span>
                            <a href="{{ $update['url'] }}" aria-label="Open {{ $update['title'] }}"{!! \App\Support\LinkAttributes::externalAttributes($update['url'] ?? null) !!}></a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif

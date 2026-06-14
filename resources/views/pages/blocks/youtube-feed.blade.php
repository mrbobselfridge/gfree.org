@php
    $videos = collect($data['videos'] ?? []);
    $channelUrl = $data['channel_url'] ?? null;
    $linkLabel = $data['youtube_link_label'] ?? 'View more on YouTube';
@endphp

<section class="sermon-index page-block page-block--bg-black">
    <div class="page-block__inner">
        <div class="sermon-index__header">
            <div class="sermon-tabs" aria-label="YouTube video filters">
                <span class="sermon-tabs__item sermon-tabs__item--active">Latest</span>
            </div>

            @if ($channelUrl)
                <a href="{{ $channelUrl }}" class="sermon-index__channel-link" target="_blank" rel="noopener noreferrer">{{ $linkLabel }}</a>
            @endif
        </div>

        @if ($videos->isNotEmpty())
            <div class="sermon-grid">
                @foreach ($videos as $video)
                    <article class="sermon-card">
                        <a href="{{ $video['url'] }}" class="sermon-card__media" aria-label="Watch {{ $video['title'] }}">
                            @if ($video['thumbnail_url'])
                                <img src="{{ $video['thumbnail_url'] }}" alt="">
                            @endif

                            <span>Watch</span>
                        </a>

                        <div class="sermon-card__content">
                            <h2>
                                <a href="{{ $video['url'] }}">{{ $video['title'] }}</a>
                            </h2>

                            @if ($video['published_label'])
                                <p>{{ $video['published_label'] }}</p>
                            @endif

                            @if ($video['description'])
                                <div>{{ $video['description'] }}</div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @elseif ($channelUrl)
            <div class="page-rich-text">
                <p>Videos are currently available on YouTube.</p>
                <p><a href="{{ $channelUrl }}" target="_blank" rel="noopener noreferrer">{{ $linkLabel }}</a></p>
            </div>
        @endif
    </div>
</section>

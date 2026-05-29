<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sermons | {{ $settings?->church_name ?? config('app.name', 'gFree Church') }}</title>
    <meta name="description" content="{{ strip_tags($hero['subtitle'] ?: $introText ?: 'Watch recent sermons from gFree Church.') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site-page concept-page concept-page--editorial concept-page--editorial-white-header concept-page--accent-color-bands">
    @include('home.partials.header')

    <main>
        <section @class(['page-hero', 'page-hero--image' => filled($hero['image_url'])])>
            @if ($hero['image_url'])
                <div class="page-hero__image" style="background-image: url('{{ $hero['image_url'] }}')"></div>
            @endif

            <div class="page-hero__content">
                @if ($hero['small_label'])
                    <p class="concept-eyebrow">{{ $hero['small_label'] }}</p>
                @endif

                <h1>{{ $hero['title'] }}</h1>

                @if ($hero['subtitle'])
                    <div class="page-hero__subtitle">{!! \App\Support\RichContent::render($hero['subtitle']) !!}</div>
                @endif
            </div>
        </section>

        <section class="sermon-index page-block page-block--bg-black">
            <div class="page-block__inner">
                @if ($introText)
                    <div class="sermon-index__intro page-rich-text">
                        {!! \App\Support\RichContent::render($introText) !!}
                    </div>
                @endif

                <div class="sermon-index__header">
                    <div class="sermon-tabs" aria-label="Sermon filters">
                        <span class="sermon-tabs__item sermon-tabs__item--active">Latest</span>
                    </div>

                    <a href="{{ $channelUrl }}" class="sermon-index__channel-link" target="_blank" rel="noopener noreferrer">{{ $channelLinkLabel }}</a>
                </div>

                @if (count($sermons))
                    <div class="sermon-grid">
                        @foreach ($sermons as $sermon)
                            <article class="sermon-card">
                                <a href="{{ $sermon['url'] }}" class="sermon-card__media" aria-label="Watch {{ $sermon['title'] }}">
                                    @if ($sermon['thumbnail_url'])
                                        <img src="{{ $sermon['thumbnail_url'] }}" alt="">
                                    @endif

                                    <span>Watch</span>
                                </a>

                                <div class="sermon-card__content">
                                    <h2>
                                        <a href="{{ $sermon['url'] }}">{{ $sermon['title'] }}</a>
                                    </h2>

                                    @if ($sermon['published_label'])
                                        <p>{{ $sermon['published_label'] }}</p>
                                    @endif

                                    @if ($sermon['description'])
                                        <div>{{ $sermon['description'] }}</div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="page-rich-text">
                        <p>Sermons are currently available on YouTube.</p>
                        <p><a href="{{ $channelUrl }}">Open the gFREE Sermons channel</a></p>
                    </div>
                @endif
            </div>
        </section>
    </main>

    @include('home.partials.footer')
</body>
</html>

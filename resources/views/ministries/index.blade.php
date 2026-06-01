<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ministries | {{ $settings?->church_name ?? config('app.name', 'gFree Church') }}</title>
    <meta name="description" content="{{ strip_tags($hero['subtitle'] ?: 'Explore ministries at gFree Church.') }}">
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

        <section class="ministry-index">
            <x-listing-search :search="$search" placeholder="Search ministries" />

            @if ($ministries->count())
                <div class="ministry-grid">
                    @foreach ($ministries as $ministry)
                        <article class="ministry-card">
                            <a class="listing-card__link" href="{{ route('ministries.show', $ministry->slug) }}">
                                @if ($ministry->image_url)
                                    <img src="{{ $ministry->image_url }}" alt="">
                                @endif

                                <div class="listing-card__content">
                                    @if ($ministry->category)
                                        <p>{{ $ministry->category }}</p>
                                    @endif

                                    <h2>{{ $ministry->name }}</h2>

                                    @if ($ministry->short_summary)
                                        <span class="listing-card__summary">{{ $ministry->short_summary }}</span>
                                    @endif

                                    <span class="listing-card__button">Learn more</span>
                                </div>
                            </a>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="page-content">
                    <p>{{ filled($search) ? 'No ministries match your search.' : 'Ministry information is coming soon.' }}</p>
                </div>
            @endif
        </section>
    </main>

    @include('home.partials.footer')
</body>
</html>

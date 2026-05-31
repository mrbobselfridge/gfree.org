<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bulletins | {{ $settings?->church_name ?? config('app.name', 'gFree Church') }}</title>
    <meta name="description" content="Recent weekly bulletins from gFree Church.">
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

        <section class="announcement-index">
            @if ($bulletins->count())
                <div class="announcement-grid">
                    @foreach ($bulletins as $bulletin)
                        <article class="announcement-card">
                            <a class="listing-card__link" href="{{ $bulletin->public_url }}">
                                <div class="listing-card__content">
                                    <p>Bulletin</p>
                                    <h2>{{ $bulletin->title }}</h2>

                                    <span class="listing-card__summary">
                                        {{ $bulletin->bulletin_date->format('F j, Y') }}
                                    </span>

                                    <span class="listing-card__button">View bulletin</span>
                                </div>
                            </a>
                        </article>
                    @endforeach
                </div>

                {{ $bulletins->links() }}
            @else
                <div class="page-content">
                    <p>There are no current bulletins.</p>
                </div>
            @endif
        </section>
    </main>

    @include('home.partials.footer')
</body>
</html>

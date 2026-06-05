<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.analytics-head')
    <title>Leadership | {{ $settings?->church_name ?? config('app.name', 'gFree Church') }}</title>
    <meta name="description" content="Meet the leadership of gFree Church.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site-page concept-page concept-page--editorial concept-page--editorial-white-header concept-page--accent-color-bands">
    @include('partials.analytics-body')

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

        <section class="leadership-index">
            <x-listing-search :search="$search" placeholder="Search leaders" />

            @if ($leaders->count())
                <div class="leadership-grid">
                    @foreach ($leaders as $leader)
                        <article class="leadership-card">
                            <a class="listing-card__link" href="{{ route('leadership.show', $leader->slug) }}">
                                @if ($leader->photo_url)
                                    <img src="{{ $leader->photo_url }}" alt="{{ $leader->name }}">
                                @endif

                                <div class="listing-card__content">
                                    @if ($leader->role)
                                        <p>{{ $leader->role }}</p>
                                    @endif

                                    <h2>{{ $leader->name }}</h2>
                                    <span class="listing-card__button">View profile</span>
                                </div>
                            </a>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="page-content">
                    <p>{{ filled($search) ? 'No leaders match your search.' : 'Leadership profiles are coming soon.' }}</p>
                </div>
            @endif
        </section>
    </main>

    @include('home.partials.footer')
</body>
</html>

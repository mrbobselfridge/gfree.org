<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Leadership | {{ $settings?->church_name ?? config('app.name', 'gFree Church') }}</title>
    <meta name="description" content="Meet the leadership of gFree Church.">
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
                    <p>{{ $hero['subtitle'] }}</p>
                @endif
            </div>
        </section>

        <section class="leadership-index">
            @if ($leaders->count())
                <div class="leadership-grid">
                    @foreach ($leaders as $leader)
                        <article class="leadership-card">
                            @if ($leader->photo_url)
                                <img src="{{ $leader->photo_url }}" alt="{{ $leader->name }}">
                            @endif

                            <div>
                                @if ($leader->role)
                                    <p>{{ $leader->role }}</p>
                                @endif

                                <h2>{{ $leader->name }}</h2>
                                <a href="{{ route('leadership.show', $leader->slug) }}">View profile</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="page-content">
                    <p>Leadership profiles are coming soon.</p>
                </div>
            @endif
        </section>
    </main>

    @include('home.partials.footer')
</body>
</html>

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $leader->name }} | {{ $settings?->church_name ?? config('app.name', 'gFree Church') }}</title>
    <meta name="description" content="{{ $leader->role ?: 'Leadership profile from gFree Church.' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site-page concept-page concept-page--editorial concept-page--editorial-white-header concept-page--accent-color-bands">
    @include('home.partials.header')

    <main>
        <section @class(['page-hero', 'page-hero--image' => filled($photoUrl)])>
            @if ($photoUrl)
                <div class="page-hero__image" style="background-image: url('{{ $photoUrl }}')"></div>
            @endif

            <div class="page-hero__content">
                <p class="concept-eyebrow">Leadership</p>
                <h1>{{ $leader->name }}</h1>

                @if ($leader->role)
                    <p>{{ $leader->role }}</p>
                @endif
            </div>
        </section>

        <article class="leadership-detail page-block page-block--bg-white">
            <div class="page-block__inner page-block__inner--narrow">
                @if ($leader->bio)
                    <div class="page-rich-text">
                        {!! \App\Support\RichContent::render($leader->bio) !!}
                    </div>
                @endif

                @if ($leader->email)
                    <a class="page-block__button" href="mailto:{{ $leader->email }}">Email {{ $leader->name }}</a>
                @endif
            </div>
        </article>
    </main>

    @include('home.partials.footer')
</body>
</html>

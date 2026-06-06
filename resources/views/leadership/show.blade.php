<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.analytics-head')
    <title>{{ $leader->name }} | {{ $settings?->church_name ?? config('app.name', 'TwyxtCo Church') }}</title>
    <meta name="description" content="{{ $leader->role ?: 'Leadership profile from TwyxtCo Church.' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site-page concept-page concept-page--editorial concept-page--editorial-white-header concept-page--accent-color-bands">
    @include('partials.analytics-body')

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

        @if (count($contentBlocks))
            @include('pages.partials.content-blocks')
        @endif
    </main>

    @include('home.partials.footer')
</body>
</html>

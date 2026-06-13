<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.analytics-head')
    <title>{{ $data['heading'] ?? 'Related Content' }} | {{ $settings?->church_name ?? config('app.name', 'TwyxtCo Church') }}</title>
    <meta name="description" content="{{ $data['intro'] ?: $page->seo_description ?: $page->intro ?: $settings?->tagline }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class([
    'site-page',
    'public-page',
    'concept-page',
    'concept-page--editorial',
    'concept-page--editorial-white-header',
    'concept-page--accent-color-bands',
    'public-page--with-site-chrome' => $page->show_site_chrome,
    'public-page--without-site-chrome' => ! $page->show_site_chrome,
    'public-page--with-page-header',
])>
    @include('partials.analytics-body')

    @if ($page->show_site_chrome)
        @include('home.partials.header')
    @endif

    <main class="public-page__main">
        <section class="page-hero">
            <div class="page-hero__content">
                <div class="page-hero__text">
                    <p class="concept-eyebrow">{{ $page->title }}</p>
                    <h1>{{ $data['heading'] ?? 'Related Content' }}</h1>

                    @if (filled($data['intro'] ?? null))
                        <p>{{ $data['intro'] }}</p>
                    @endif
                </div>
            </div>
        </section>

        <section @class([
            'concept-updates',
            'concept-updates--bar',
            'concept-updates--bg-' . ($data['background'] ?? 'white'),
        ])>
            <div class="concept-updates__inner">
                @include('pages.partials.related-content-grid', ['items' => collect($data['items'] ?? [])])
            </div>
        </section>
    </main>

    @if ($page->show_site_chrome)
        @include('home.partials.footer')
    @endif
</body>
</html>

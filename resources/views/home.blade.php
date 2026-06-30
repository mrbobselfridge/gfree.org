<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.analytics-head')
    <title>{{ $pageTitle }}</title>
    @if (filled($pageDescription))
        <meta name="description" content="{{ $pageDescription }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.site-design')
    @include('partials.custom-head-scripts')
</head>
<body @class([
    'site-home',
    'concept-page',
    'concept-page--editorial',
    'concept-page--editorial-white-header',
    'concept-page--accent-color-bands',
    'concept-page--hero-default',
    'concept-page--sections-bands-color',
    'site-home--' . $theme['layout'],
    'site-home--accent-' . $theme['accent'],
])>
    @include('partials.custom-body-top-scripts')
    @include('partials.analytics-body')

    @include('partials.site-top-chrome')
    @include('home.partials.header')

    <main>
        @include('home.partials.hero')
        @include('home.partials.content-blocks')
    </main>

    @include('home.partials.footer')
    @include('partials.custom-body-bottom-scripts')
</body>
</html>

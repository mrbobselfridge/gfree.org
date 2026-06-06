<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.analytics-head')
    <title>{{ $bulletin->title }} | {{ $settings?->church_name ?? config('app.name', 'TwyxtCo Church') }}</title>
    <meta name="description" content="Bulletin for {{ $bulletin->bulletin_date->format('F j, Y') }} from TwyxtCo Church.">
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

                <h1>{{ $bulletin->title }}</h1>
                <p>{{ $bulletin->bulletin_date->format('F j, Y') }}</p>
            </div>
        </section>

        <article class="page-block page-block--bg-white">
            <div class="page-block__inner page-block__inner--narrow">
                @if ($bulletin->extracted_html)
                    <div class="page-rich-text">
                        {!! \App\Support\RichContent::render($bulletin->extracted_html) !!}
                    </div>
                @endif

                @if ($pdfUrl)
                    <a class="page-block__button" href="{{ $pdfUrl }}" target="_blank" rel="noopener noreferrer">Open original PDF</a>
                @endif
            </div>
        </article>
    </main>

    @include('home.partials.footer')
</body>
</html>

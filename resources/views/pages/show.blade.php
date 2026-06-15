<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.analytics-head')
    <title>{{ $page->seo_title ?: $page->title }} | {{ $settings?->church_name ?? config('app.name', 'TwyxtCo Church') }}</title>
    <meta name="description" content="{{ $page->seo_description ?: $page->intro ?: $settings?->tagline }}">
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
    'public-page--with-page-header' => $page->show_page_header,
    'public-page--without-page-header' => ! $page->show_page_header,
])>
    @include('partials.analytics-body')

    @if ($page->show_site_chrome)
        @include('home.partials.header')
    @endif

    <main class="public-page__main">
        @if ($page->show_page_header)
            @php($hasPageMessage = filled($page->message))

            <section @class([
                'page-hero',
                'page-hero--image' => filled($heroImageUrl),
                'page-hero--page-message' => $hasPageMessage,
            ])>
                @if ($heroImageUrl)
                    <div class="page-hero__image" style="background-image: url('{{ $heroImageUrl }}')"></div>
                @endif

                <div class="page-hero__content">
                    <div class="page-hero__text">
                        @if ($page->hero_label)
                            <p class="concept-eyebrow">{{ $page->hero_label }}</p>
                        @endif

                        <h1>{{ $page->title }}</h1>

                        @if ($page->intro)
                            <p>{{ $page->intro }}</p>
                        @endif
                    </div>

                    @if ($hasPageMessage)
                        <div class="ministry-hero-contact page-hero-message" aria-label="Page message">
                            <div class="page-hero-message__body">
                                @php($pageMessage = trim((string) $page->message))

                                @if ($pageMessage !== strip_tags($pageMessage))
                                    {!! $pageMessage !!}
                                @else
                                    @foreach (preg_split('/\R{2,}/', $pageMessage) as $paragraph)
                                        <p>{!! nl2br(e($paragraph)) !!}</p>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if (count($contentBlocks))
            @include('pages.partials.content-blocks')
        @else
            <section class="page-content">
                @if ($page->body)
                @foreach (preg_split('/\R{2,}/', trim($page->body)) as $paragraph)
                    <p>{!! nl2br(e($paragraph)) !!}</p>
                @endforeach
                @else
                    <p>This page is ready for content.</p>
                @endif
            </section>
        @endif
    </main>

    @if ($page->show_site_chrome)
        @include('home.partials.footer')
    @endif
</body>
</html>

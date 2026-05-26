<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $announcement->title }} | {{ $settings?->church_name ?? config('app.name', 'gFree Church') }}</title>
    <meta name="description" content="{{ $announcement->summary ?? 'Announcement from gFree Church.' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site-page concept-page concept-page--editorial concept-page--editorial-white-header concept-page--accent-color-bands">
    @include('home.partials.header')

    <main>
        <section @class(['page-hero', 'page-hero--image' => filled($imageUrl)])>
            @if ($imageUrl)
                <div class="page-hero__image" style="background-image: url('{{ $imageUrl }}')"></div>
            @endif

            <div class="page-hero__content">
                <p class="concept-eyebrow">{{ $announcement->is_featured ? 'Featured' : 'Announcement' }}</p>
                <h1>{{ $announcement->title }}</h1>

                @if ($announcement->summary)
                    <p>{{ $announcement->summary }}</p>
                @endif
            </div>
        </section>

        <article class="announcement-detail page-block page-block--bg-{{ $announcement->background ?: 'white' }}">
            <div class="page-block__inner page-block__inner--narrow">
                @if ($announcement->body)
                    <div class="page-rich-text">
                        {!! $announcement->body !!}
                    </div>
                @endif

                @if ($announcement->cta_label && $announcement->cta_url)
                    <a class="page-block__button" href="{{ $announcement->cta_url }}">{{ $announcement->cta_label }}</a>
                @endif
            </div>
        </article>
    </main>

    @include('home.partials.footer')
</body>
</html>

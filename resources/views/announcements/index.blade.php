<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Announcements | {{ $settings?->church_name ?? config('app.name', 'gFree Church') }}</title>
    <meta name="description" content="Current announcements and updates from gFree Church.">
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
            @if ($announcements->count())
                <div class="announcement-grid">
                    @foreach ($announcements as $announcement)
                        <article class="announcement-card">
                            <a class="listing-card__link" href="{{ route('announcements.show', $announcement->slug) }}">
                                @if ($announcement->image_url)
                                    <img src="{{ $announcement->image_url }}" alt="">
                                @endif

                                <div class="listing-card__content">
                                    <p>{{ $announcement->is_featured ? 'Featured' : 'Announcement' }}</p>
                                    <h2>{{ $announcement->title }}</h2>

                                    @if ($announcement->summary)
                                        <span class="listing-card__summary">{{ $announcement->summary }}</span>
                                    @endif

                                    <span class="listing-card__button">Read more</span>
                                </div>
                            </a>
                        </article>
                    @endforeach
                </div>

                {{ $announcements->links() }}
            @else
                <div class="page-content">
                    <p>There are no current announcements.</p>
                </div>
            @endif
        </section>
    </main>

    @include('home.partials.footer')
</body>
</html>

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.analytics-head')
    <title>{{ $ministry->name }} | {{ $settings?->church_name ?? config('app.name', 'gFree Church') }}</title>
    <meta name="description" content="{{ $ministry->short_summary ?: 'Ministry at gFree Church.' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site-page concept-page concept-page--editorial concept-page--editorial-white-header concept-page--accent-color-bands">
    @include('partials.analytics-body')

    @include('home.partials.header')

    <main>
        @php($hasLeaderContact = $ministry->leader_name || $ministry->leader_email || $ministry->leader_phone)
        @php($hasHeroDetails = $hasLeaderContact || $detailItems->count() || $ministry->one_church_url)

        <section @class([
            'page-hero',
            'page-hero--image' => filled($heroImageUrl),
            'page-hero--ministry-detail' => $hasHeroDetails,
        ])>
            @if ($heroImageUrl)
                <div class="page-hero__image" style="background-image: url('{{ $heroImageUrl }}')"></div>
            @endif

            <div class="page-hero__content">
                <div class="page-hero__text">
                    <p class="concept-eyebrow">{{ $ministry->category ?: 'Ministry' }}</p>
                    <h1>{{ $ministry->name }}</h1>

                    @if ($ministry->short_summary)
                        <p>{{ $ministry->short_summary }}</p>
                    @endif
                </div>

                @if ($hasHeroDetails)
                    <div class="ministry-hero-contact" aria-label="Ministry details">
                        <span>{{ $hasLeaderContact ? 'Ministry Leader' : 'Ministry Details' }}</span>

                        @if ($ministry->leader_name)
                            <strong>{{ $ministry->leader_name }}</strong>
                        @endif

                        @if ($ministry->leader_email || $ministry->leader_phone)
                            <div class="ministry-hero-contact__links">
                                @if ($ministry->leader_email)
                                    <a href="mailto:{{ $ministry->leader_email }}">{{ $ministry->leader_email }}</a>
                                @endif

                                @if ($ministry->leader_phone)
                                    <a href="tel:{{ preg_replace('/\D+/', '', $ministry->leader_phone) }}">{{ $ministry->leader_phone }}</a>
                                @endif
                            </div>
                        @endif

                        @if ($detailItems->count())
                            <dl class="ministry-hero-contact__details">
                                @foreach ($detailItems as $item)
                                    <div>
                                        <dt>{{ $item['label'] }}</dt>
                                        <dd>{{ $item['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif

                        @if ($ministry->one_church_url)
                            <a class="page-block__button ministry-hero-contact__button" href="{{ $ministry->one_church_url }}"{!! \App\Support\LinkAttributes::externalAttributes($ministry->one_church_url) !!}>Open in One Church</a>
                        @endif
                    </div>
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

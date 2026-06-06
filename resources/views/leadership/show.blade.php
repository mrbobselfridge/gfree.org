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
        @php($hasLeaderContact = $leader->email || $leader->phone_number || $leader->availability)

        <section @class([
            'page-hero',
            'page-hero--image' => filled($photoUrl),
            'page-hero--leader-detail' => $hasLeaderContact,
        ])>
            @if ($photoUrl)
                <div class="page-hero__image" style="background-image: url('{{ $photoUrl }}')"></div>
            @endif

            <div class="page-hero__content">
                <div class="page-hero__text">
                    <p class="concept-eyebrow">Leadership</p>
                    <h1>{{ $leader->name }}</h1>

                    @if ($leader->role)
                        <p>{{ $leader->role }}</p>
                    @endif
                </div>

                @if ($hasLeaderContact)
                    <div class="ministry-hero-contact leadership-hero-contact" aria-label="Leader contact details">
                        <span>Leader Contact</span>
                        <strong>{{ $leader->name }}</strong>

                        @if ($leader->email || $leader->phone_number)
                            <div class="ministry-hero-contact__links">
                                @if ($leader->email)
                                    <a href="mailto:{{ $leader->email }}">{{ $leader->email }}</a>
                                @endif

                                @if ($leader->phone_number)
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $leader->phone_number) }}">{{ $leader->phone_number }}</a>
                                @endif
                            </div>
                        @endif

                        @if ($leader->availability)
                            <dl class="ministry-hero-contact__details">
                                <div>
                                    <dt>Availability</dt>
                                    <dd>{{ $leader->availability }}</dd>
                                </div>
                            </dl>
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

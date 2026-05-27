<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $ministry->name }} | {{ $settings?->church_name ?? config('app.name', 'gFree Church') }}</title>
    <meta name="description" content="{{ $ministry->short_summary ?: 'Ministry at gFree Church.' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site-page concept-page concept-page--editorial concept-page--editorial-white-header concept-page--accent-color-bands">
    @include('home.partials.header')

    <main>
        <section @class(['page-hero', 'page-hero--image' => filled($heroImageUrl)])>
            @if ($heroImageUrl)
                <div class="page-hero__image" style="background-image: url('{{ $heroImageUrl }}')"></div>
            @endif

            <div class="page-hero__content">
                <p class="concept-eyebrow">{{ $ministry->category ?: 'Ministry' }}</p>
                <h1>{{ $ministry->name }}</h1>

                @if ($ministry->short_summary)
                    <p>{{ $ministry->short_summary }}</p>
                @endif
            </div>
        </section>

        <article class="ministry-detail page-block page-block--bg-white">
            @php($hasSidebar = $detailItems->count() || $ministry->leader_email || $ministry->one_church_url)

            <div @class(['page-block__inner', 'ministry-detail__layout', 'ministry-detail__layout--single' => ! $hasSidebar])>
                <div class="ministry-detail__main">
                    @if ($ministry->description)
                        <div class="page-rich-text">
                            {!! $ministry->description !!}
                        </div>
                    @else
                        <div class="page-rich-text">
                            <p>More information about this ministry is coming soon.</p>
                        </div>
                    @endif

                    @if ($ministry->embed_code)
                        <div class="ministry-detail__embed">
                            {!! $ministry->embed_code !!}
                        </div>
                    @endif
                </div>

                @if ($hasSidebar)
                    <aside class="ministry-detail__sidebar" aria-label="Ministry details">
                        @if ($detailItems->count())
                            <dl>
                                @foreach ($detailItems as $item)
                                    <div>
                                        <dt>{{ $item['label'] }}</dt>
                                        <dd>{{ $item['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif

                        @if ($ministry->leader_email)
                            <a class="page-block__button" href="mailto:{{ $ministry->leader_email }}">Contact {{ $ministry->leader_name ?: 'this ministry' }}</a>
                        @endif

                        @if ($ministry->one_church_url)
                            <a class="page-block__button ministry-detail__secondary-button" href="{{ $ministry->one_church_url }}">Open in One Church</a>
                        @endif
                    </aside>
                @endif
            </div>
        </article>
    </main>

    @include('home.partials.footer')
</body>
</html>

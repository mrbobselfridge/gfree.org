<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->seo_title ?: $page->title }} | {{ $settings?->church_name ?? config('app.name', 'gFree Church') }}</title>
    <meta name="description" content="{{ $page->seo_description ?: $page->intro ?: $settings?->tagline }}">
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
                @if ($page->hero_label)
                    <p class="concept-eyebrow">{{ $page->hero_label }}</p>
                @endif

                <h1>{{ $page->title }}</h1>

                @if ($page->intro)
                    <p>{{ $page->intro }}</p>
                @endif
            </div>
        </section>

        @if (count($contentBlocks))
            <div class="page-blocks">
                @foreach ($contentBlocks as $block)
                    @php($data = $block['data'] ?? [])

                    @switch($block['type'])
                        @case('text')
                            @include('pages.blocks.text')
                            @break

                        @case('image_text')
                            @include('pages.blocks.image-text')
                            @break

                        @case('process_steps')
                            @include('pages.blocks.process-steps')
                            @break

                        @case('cta')
                            @include('pages.blocks.cta')
                            @break

                        @case('link_cards')
                            @include('pages.blocks.link-cards')
                            @break
                    @endswitch
                @endforeach
            </div>
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

    @include('home.partials.footer')
</body>
</html>

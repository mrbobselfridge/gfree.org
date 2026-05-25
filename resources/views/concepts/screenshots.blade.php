<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>gFree Concept Screenshots</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="concept-screens">
    <header class="concept-screens__header">
        <div>
            <img src="{{ asset('images/gfree-logo.png') }}" alt="gFree Church">
            <h1>Concept Screenshots</h1>
        </div>
        <a href="{{ url('/concepts') }}">Open Concepts</a>
    </header>

    <main class="concept-screens__grid">
        @foreach ($screenshots as $screenshot)
            <article class="concept-screens__item">
                <a href="{{ $screenshot['url'] }}" target="_blank" rel="noreferrer">
                    <img src="{{ $screenshot['url'] }}" alt="{{ $screenshot['label'] }}">
                </a>
                <div>
                    <strong>{{ $screenshot['label'] }}</strong>
                    <span>{{ $screenshot['file'] }}</span>
                </div>
            </article>
        @endforeach
    </main>
</body>
</html>

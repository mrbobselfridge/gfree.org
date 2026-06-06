<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TwyxtCo Concepts</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="concept-index">
    <main class="concept-index__wrap">
        <img class="concept-logo-img concept-logo-img--index" src="{{ asset('images/twyxtco-logo.png') }}" alt="TwyxtCo Church">

        <h1>Homepage Concepts</h1>
        <p>These are static design directions for comparing feel before connecting the real Laravel content. The dark direction is intentionally pushed harder.</p>

        <div class="concept-index__grid">
            @foreach ($concepts as $slug => $name)
                <a href="{{ url("/concepts/{$slug}") }}">
                    <span>{{ $name }}</span>
                    <small>View concept</small>
                </a>
            @endforeach
        </div>
    </main>
</body>
</html>

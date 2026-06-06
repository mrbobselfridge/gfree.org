<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $concept['name'] }} | TwyxtCo Concept</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class([
    'concept-page',
    'concept-page--' . $concept['variant'],
    'concept-page--' . ($concept['headerVariant'] ?? '') => filled($concept['headerVariant'] ?? null),
    'concept-page--accent-' . ($concept['accentMode'] ?? '') => filled($concept['accentMode'] ?? null),
    'concept-page--hero-' . ($concept['heroMode'] ?? '') => filled($concept['heroMode'] ?? null),
    'concept-page--sections-' . str_replace(' ', '-', $concept['sectionMode'] ?? '') => filled($concept['sectionMode'] ?? null),
])>
    <header class="concept-header">
        <a href="{{ url('/concepts') }}" class="concept-logo-link" aria-label="TwyxtCo Church concepts">
            <img class="concept-logo-img" src="{{ asset('images/twyxtco-logo.png') }}" alt="TwyxtCo Church">
        </a>

        <nav class="concept-nav" aria-label="Concept navigation">
            <a href="#">New Here</a>
            <a href="#">Sundays</a>
            <a href="#">Ministries</a>
            <a href="#">Messages</a>
            <a href="#" class="concept-nav__give">Give</a>
        </nav>
    </header>

    <main>
        <section class="concept-hero">
            <div class="concept-hero__image" style="background-image: url('{{ $concept['heroImage'] }}')"></div>
            <div class="concept-hero__content">
                <p class="concept-eyebrow">{{ $concept['eyebrow'] }}</p>
                <h1>{{ $concept['headline'] }}</h1>
                <p>{{ $concept['subhead'] }}</p>
                <div class="concept-actions">
                    <a href="{{ $concept['primaryUrl'] }}" class="concept-button concept-button--primary">{{ $concept['primaryCta'] }}</a>
                    <a href="{{ $concept['secondaryUrl'] }}" class="concept-button concept-button--secondary">{{ $concept['secondaryCta'] }}</a>
                </div>
            </div>
        </section>

        <section class="concept-service-strip" aria-label="Sunday details">
            <div>
                <span>Sunday</span>
                <strong>9:00 & 10:45 AM</strong>
            </div>
            <div>
                <span>Visit</span>
                <strong>305 Keystone Hill Road</strong>
            </div>
            <div>
                <span>Next Step</span>
                <strong>Connect Card & Prayer</strong>
            </div>
        </section>

        <section class="concept-section concept-section--intro">
            <div>
                <p class="concept-eyebrow">Start here</p>
                <h2>Everything a guest needs without digging.</h2>
            </div>
            <p>The homepage can make the first visit obvious: where to go, what kids do, how long service lasts, how to watch online, and what step to take next.</p>
        </section>

        <section class="concept-card-row" aria-label="Next steps">
            <article>
                <span>01</span>
                <h3>Visit Sunday</h3>
                <p>Service times, what to expect, kids check-in, and where to go.</p>
            </article>
            <article>
                <span>02</span>
                <h3>Find Community</h3>
                <p>Groups, kids, students, and ways to belong beyond Sunday.</p>
            </article>
            <article>
                <span>03</span>
                <h3>Start Serving</h3>
                <p>A direct path into teams, prayer, events, giving, and One Church.</p>
            </article>
        </section>

        <section class="concept-process" aria-label="Serving process">
            <div class="concept-process__intro">
                <p class="concept-eyebrow">Ready to serve?</p>
                <h2>Every step matters.</h2>
            </div>
            <div class="concept-process__steps">
                <article>
                    <strong>Fill out the form</strong>
                    <span>Tell us where you are interested.</span>
                </article>
                <article>
                    <strong>Talk with a leader</strong>
                    <span>Find a team that fits your gifts.</span>
                </article>
                <article>
                    <strong>Begin serving</strong>
                    <span>Use what God has given you.</span>
                </article>
            </div>
        </section>

        <section class="concept-feature">
            <div class="concept-feature__media"></div>
            <div>
                <p class="concept-eyebrow">Featured</p>
                <h2>One Church handles the moving parts.</h2>
                <p>Forms, event registrations, giving, and ministry signups can stay in One Church while the website stays focused on welcome, clarity, and direction.</p>
                <a href="#" class="concept-text-link">Open One Church</a>
            </div>
        </section>

        <section class="concept-updates">
            <div class="concept-updates__header">
                <h2>Latest at TwyxtCo</h2>
                <a href="#">View all</a>
            </div>
            <div class="concept-updates__grid">
                <article>
                    <p>Announcement</p>
                    <h3>Family ministry night</h3>
                    <span>Registration open now</span>
                </article>
                <article>
                    <p>Message</p>
                    <h3>Grace for the week ahead</h3>
                    <span>Watch the latest sermon</span>
                </article>
                <article>
                    <p>Ministry</p>
                    <h3>Students summer schedule</h3>
                    <span>See upcoming gatherings</span>
                </article>
            </div>
        </section>
    </main>

    <footer class="concept-footer">
        <div class="concept-switcher" aria-label="Switch concept">
            @foreach ($allConcepts as $slug => $item)
                <a href="{{ url("/concepts/{$slug}") }}" @class(['is-active' => $slug === $currentSlug])>{{ $item['name'] }}</a>
            @endforeach
        </div>
    </footer>
</body>
</html>

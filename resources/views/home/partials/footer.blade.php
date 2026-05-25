<footer class="concept-footer site-footer">
    <div>
        <img class="concept-logo-img site-footer__logo" src="{{ asset('images/gfree-logo.png') }}" alt="{{ $settings?->church_name ?? 'gFree Church' }}">
        <p>{{ $settings?->tagline ?? 'Grace Free Church' }}</p>
    </div>

    <div class="site-footer__details">
        @if ($settings?->address)
            <span>{{ $settings->address }}</span>
        @endif
        @if ($settings?->email)
            <a href="mailto:{{ $settings->email }}">{{ $settings->email }}</a>
        @endif
        @foreach ($socialLinks as $link)
            <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
        @endforeach
    </div>
</footer>

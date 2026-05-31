<footer class="concept-footer site-footer">
    <div class="site-footer__brand">
        <a href="{{ url('/') }}" aria-label="{{ $settings?->church_name ?? 'gFree Church' }} home">
            <img class="concept-logo-img site-footer__logo" src="{{ asset('images/gfree-logo.png') }}" alt="{{ $settings?->church_name ?? 'gFree Church' }}">
        </a>
    </div>

    <div class="site-footer__block site-footer__address">
        @if (filled($settings?->address))
            <span class="site-footer__label">Address</span>
            <div class="site-footer__value">
                {!! \App\Support\RichContent::render($settings->address) !!}
            </div>
        @endif
    </div>

    <div class="site-footer__block site-footer__contact">
        @if ($settings?->phone)
            <span class="site-footer__label">Phone</span>
            <a class="site-footer__value" href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->phone) }}">{{ $settings->phone }}</a>
        @endif
        @if ($settings?->email)
            <span class="site-footer__label">Email</span>
            <a class="site-footer__value" href="mailto:{{ $settings->email }}">{{ $settings->email }}</a>
        @endif
    </div>

    <div class="site-footer__block site-footer__social" aria-label="Social media links">
        @if ($socialLinks->isNotEmpty())
            <span class="site-footer__label">Social</span>
        @endif
        @foreach ($socialLinks as $link)
            <a class="site-footer__social-link site-footer__social-link--{{ str($link['label'])->lower()->slug() }}" href="{{ $link['url'] }}" aria-label="{{ $link['label'] }}" title="{{ $link['label'] }}" target="_blank" rel="noopener noreferrer">
                @switch($link['label'])
                    @case('Facebook')
                        <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                            <path d="M14.1 8.3V6.9c0-.7.5-1.1 1.2-1.1h1.6V3.1c-.8-.1-1.6-.2-2.4-.2-2.5 0-4.2 1.5-4.2 4.2v1.2H7.7v3h2.6V21h3.2v-9.7h2.7l.5-3h-3.6Z" />
                        </svg>
                        @break

                    @case('Instagram')
                        <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                            <rect x="4" y="4" width="16" height="16" rx="4.2" />
                            <circle cx="12" cy="12" r="3.5" />
                            <circle cx="16.8" cy="7.2" r="1" />
                        </svg>
                        @break

                    @case('YouTube')
                        <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                            <rect x="3" y="6.5" width="18" height="11" rx="3" />
                            <path d="m10.3 9.4 5 3.1-5 3.1Z" />
                        </svg>
                        @break

                    @default
                        <span aria-hidden="true">{{ strtoupper(substr($link['label'], 0, 1)) }}</span>
                @endswitch
                <span class="site-footer__social-label">{{ $link['label'] }}</span>
            </a>
        @endforeach
    </div>
</footer>

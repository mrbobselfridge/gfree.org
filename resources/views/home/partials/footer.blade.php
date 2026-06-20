<footer class="concept-footer site-footer">
    <div class="site-footer__brand">
        <a href="{{ url('/') }}" aria-label="{{ $settings?->church_name ?? 'TwyxtCo Church' }} home">
            <img class="concept-logo-img site-footer__logo" src="{{ $settings?->logoUrl() ?? asset('images/twyxtco-logo.png') }}" alt="{{ $settings?->church_name ?? 'TwyxtCo Church' }}">
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
            @php($icon = $link['icon'] ?? str($link['label'])->lower()->slug()->toString())
            <a class="site-footer__social-link site-footer__social-link--{{ str($icon)->slug() }}" href="{{ $link['url'] }}" aria-label="{{ $link['label'] }}" title="{{ $link['label'] }}" target="_blank" rel="noopener noreferrer">
                @if (filled($link['image_url'] ?? null))
                    <img src="{{ $link['image_url'] }}" alt="">
                @else
                    @switch($icon)
                    @case('facebook')
                        <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                            <path d="M14.1 8.3V6.9c0-.7.5-1.1 1.2-1.1h1.6V3.1c-.8-.1-1.6-.2-2.4-.2-2.5 0-4.2 1.5-4.2 4.2v1.2H7.7v3h2.6V21h3.2v-9.7h2.7l.5-3h-3.6Z" />
                        </svg>
                        @break

                    @case('instagram')
                        <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                            <rect x="4" y="4" width="16" height="16" rx="4.2" />
                            <circle cx="12" cy="12" r="3.5" />
                            <circle cx="16.8" cy="7.2" r="1" />
                        </svg>
                        @break

                    @case('youtube')
                        <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                            <rect x="3" y="6.5" width="18" height="11" rx="3" />
                            <path d="m10.3 9.4 5 3.1-5 3.1Z" />
                        </svg>
                        @break

                    @case('tiktok')
                        <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                            <path d="M14.6 3.2v10.2a4.7 4.7 0 1 1-4.7-4.7c.4 0 .8.1 1.2.2v3.1a1.7 1.7 0 1 0 1.1 1.6V3.2h2.4Z" />
                            <path d="M14.6 3.2c.4 2.3 1.9 3.8 4.2 4.1v3.1c-1.7-.1-3.1-.7-4.2-1.6" />
                        </svg>
                        @break

                    @case('linkedin')
                        <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                            <path d="M5 9h3.2v10H5V9Zm1.6-4.8a1.9 1.9 0 1 1 0 3.8 1.9 1.9 0 0 1 0-3.8ZM10.2 9h3.1v1.4c.5-.8 1.5-1.7 3.2-1.7 3.4 0 4 2.2 4 5.1V19h-3.2v-4.6c0-1.1 0-2.5-1.5-2.5s-1.8 1.2-1.8 2.4V19h-3.2V9Z" />
                        </svg>
                        @break

                    @case('google-business-profile')
                        <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                            <path d="M5 9.2 6.2 4h11.6L19 9.2" />
                            <path d="M5 9.2c0 1.2 1 2.2 2.2 2.2s2.2-1 2.2-2.2c0 1.2 1 2.2 2.2 2.2s2.2-1 2.2-2.2c0 1.2 1 2.2 2.2 2.2S19 10.4 19 9.2" />
                            <path d="M6.5 11.2V20h11v-8.8" />
                            <path d="M9.1 20v-5.1h5.8V20" />
                        </svg>
                        @break

                    @case('pinterest')
                        <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                            <path d="M12.2 3.2c-4.6 0-7 3.1-7 5.8 0 1.6.6 3 1.9 3.5.2.1.4 0 .5-.2l.2-.9c.1-.3 0-.4-.2-.7-.4-.5-.7-1-.7-1.8 0-2.3 1.8-4.4 4.7-4.4 2.6 0 4 1.5 4 3.6 0 2.7-1.2 5-3 5-1 0-1.8-.8-1.5-1.9.3-1.2.9-2.5.9-3.4 0-.8-.4-1.4-1.3-1.4-1 0-1.8 1-1.8 2.4 0 .9.3 1.5.3 1.5l-1.2 5.2c-.4 1.5-.1 3.3 0 3.5 0 .1.2.1.3 0 .1-.2 1.4-1.8 1.8-3.3l.6-2.2c.6 1.1 1.6 1.8 2.9 1.8 3.8 0 6.4-3.4 6.4-8 0-3.5-3-6.7-7.6-6.7Z" />
                        </svg>
                        @break

                    @case('x')
                        <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                            <path d="m4 4 6.7 8.8L4.4 20h2.8l4.8-5.5 4.2 5.5H20l-7-9.2L18.9 4h-2.8l-4.4 5.1L7.8 4H4Z" />
                        </svg>
                        @break

                    @case('threads')
                        <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                            <path d="M12.1 21c-5.1 0-8-3.4-8-9s2.9-9 7.8-9c4.2 0 6.8 2.3 7.4 6.4" />
                            <path d="M8.6 8.1c.8-1 2-1.5 3.4-1.5 2.2 0 3.7 1.2 3.9 3.4.1.6.1 1.2.1 1.8" />
                            <path d="M15.9 11.1c-.8-.4-1.8-.6-2.9-.6-2.6 0-4.1 1.2-4.1 3.1 0 1.7 1.3 2.9 3.3 2.9 2.2 0 3.6-1.4 3.7-3.5.1-2.6-.1-3-.1-3" />
                        </svg>
                        @break

                    @default
                        <span aria-hidden="true">{{ strtoupper(substr($link['label'], 0, 1)) }}</span>
                    @endswitch
                @endif
                <span class="site-footer__social-label">{{ $link['label'] }}</span>
            </a>
        @endforeach
    </div>
</footer>

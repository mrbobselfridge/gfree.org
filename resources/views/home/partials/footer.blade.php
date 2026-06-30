<footer class="concept-footer site-footer">
    @php($footerAddress = $settings?->siteVariableValue('address'))

    <div class="site-footer__brand">
        <a href="{{ url('/') }}" aria-label="{{ $settings?->church_name ?? 'TwyxtCo Church' }} home">
            <img class="concept-logo-img site-footer__logo" src="{{ $settings?->logoUrl() ?? asset('images/twyxtco-logo.png') }}" alt="{{ $settings?->church_name ?? 'TwyxtCo Church' }}">
        </a>
    </div>

    <div class="site-footer__block site-footer__address">
        @if (\App\Support\RichContent::hasRenderableContent($footerAddress))
            <span class="site-footer__label">Address</span>
            <div class="site-footer__value">
                {!! \App\Support\RichContent::render($footerAddress) !!}
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
            <span class="site-footer__label">Socials & Links</span>
        @endif
        @foreach ($socialLinks as $link)
            @php($icon = $link['icon'] ?? str($link['label'])->lower()->slug()->toString())
            <a class="site-footer__social-link site-footer__social-link--{{ str($icon)->slug() }}" href="{{ $link['url'] }}" aria-label="{{ $link['label'] }}" title="{{ $link['label'] }}" target="_blank" rel="noopener noreferrer">
                @if (filled($link['image_url'] ?? null))
                    <img src="{{ $link['image_url'] }}" alt="">
                @else
                    @include('partials.social-icon', ['icon' => $icon, 'label' => $link['label']])
                @endif
                <span class="site-footer__social-label">{!! \App\Support\SiteVariables::renderText($link['label'], $settings ?? null) !!}</span>
            </a>
        @endforeach
    </div>
</footer>

<header class="concept-header" data-site-header>
    <a href="{{ url('/') }}" class="concept-logo-link" aria-label="{{ $settings?->church_name ?? 'TwyxtCo Church' }} home">
        <img class="concept-logo-img" src="{{ asset('images/twyxtco-logo.png') }}" alt="{{ $settings?->church_name ?? 'TwyxtCo Church' }}">
    </a>

    <button
        type="button"
        class="concept-nav-toggle"
        data-nav-toggle
        aria-expanded="false"
        aria-controls="primary-navigation"
    >
        <span class="concept-nav-toggle__bars" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
        </span>
        <span>Menu</span>
    </button>

    <nav class="concept-nav" id="primary-navigation" data-nav-menu aria-label="Primary navigation">
        @foreach ($headerLinks as $link)
            @php
                $children = collect(data_get($link, 'children', []))
                    ->filter(fn ($child): bool => filled(data_get($child, 'label')) && filled(data_get($child, 'url')));
                $submenuId = 'primary-navigation-submenu-'.$loop->index;
            @endphp

            @if ($children->isNotEmpty())
                <div class="concept-nav__item concept-nav__item--has-children">
                    <div class="concept-nav__parent">
                        <a
                            href="{{ data_get($link, 'url') }}"
                            class="concept-nav__link"
                            aria-haspopup="true"
                            @if (data_get($link, 'opens_in_new_tab', false) === true) target="_blank" rel="noreferrer" @endif
                        >
                            {{ data_get($link, 'label') }}
                        </a>

                        <button
                            type="button"
                            class="concept-nav__submenu-toggle"
                            data-subnav-toggle
                            aria-expanded="false"
                            aria-controls="{{ $submenuId }}"
                            aria-label="Toggle {{ data_get($link, 'label') }} links"
                        >
                            <span aria-hidden="true"></span>
                        </button>
                    </div>

                    <div class="concept-nav__dropdown" id="{{ $submenuId }}" data-subnav-panel aria-label="{{ data_get($link, 'label') }} links">
                        @foreach ($children as $child)
                            <a
                                href="{{ data_get($child, 'url') }}"
                                @if (data_get($child, 'opens_in_new_tab', false) === true) target="_blank" rel="noreferrer" @endif
                            >
                                {{ data_get($child, 'label') }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <a
                    href="{{ data_get($link, 'url') }}"
                    class="concept-nav__link"
                    @if (data_get($link, 'opens_in_new_tab', false) === true) target="_blank" rel="noreferrer" @endif
                >
                    {{ data_get($link, 'label') }}
                </a>
            @endif
        @endforeach

    </nav>
</header>

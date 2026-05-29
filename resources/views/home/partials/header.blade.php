<header class="concept-header">
    <a href="{{ url('/') }}" class="concept-logo-link" aria-label="{{ $settings?->church_name ?? 'gFree Church' }} home">
        <img class="concept-logo-img" src="{{ asset('images/gfree-logo.png') }}" alt="{{ $settings?->church_name ?? 'gFree Church' }}">
    </a>

    <nav class="concept-nav" aria-label="Primary navigation">
        @foreach ($headerLinks as $link)
            @php
                $children = collect(data_get($link, 'children', []))
                    ->filter(fn ($child): bool => filled(data_get($child, 'label')) && filled(data_get($child, 'url')));
            @endphp

            @if ($children->isNotEmpty())
                <div class="concept-nav__item concept-nav__item--has-children">
                    <a
                        href="{{ data_get($link, 'url') }}"
                        class="concept-nav__link"
                        aria-haspopup="true"
                        @if (data_get($link, 'opens_in_new_tab', false) === true) target="_blank" rel="noreferrer" @endif
                    >
                        {{ data_get($link, 'label') }}
                    </a>

                    <div class="concept-nav__dropdown" aria-label="{{ data_get($link, 'label') }} links">
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

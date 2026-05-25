<header class="concept-header">
    <a href="{{ url('/') }}" class="concept-logo-link" aria-label="{{ $settings?->church_name ?? 'gFree Church' }} home">
        <img class="concept-logo-img" src="{{ asset('images/gfree-logo.png') }}" alt="{{ $settings?->church_name ?? 'gFree Church' }}">
    </a>

    <nav class="concept-nav" aria-label="Primary navigation">
        @foreach ($headerLinks as $link)
            <a
                href="{{ data_get($link, 'url') }}"
                @if (data_get($link, 'opens_in_new_tab', false) === true) target="_blank" rel="noreferrer" @endif
            >
                {{ data_get($link, 'label') }}
            </a>
        @endforeach

        <a href="{{ $settings?->giving_url ?: '/give' }}" class="concept-nav__give">Give</a>
    </nav>
</header>

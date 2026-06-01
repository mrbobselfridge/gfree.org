@props([
    'search' => '',
    'placeholder' => 'Search',
])

<form class="listing-search" method="GET" action="{{ url()->current() }}">
    <label for="listing-search-input">{{ $placeholder }}</label>
    <div class="listing-search__controls">
        <input
            id="listing-search-input"
            type="search"
            name="search"
            value="{{ $search }}"
            placeholder="{{ $placeholder }}"
        >

        <button type="submit">Search</button>

        @if (filled($search))
            <a href="{{ url()->current() }}">Clear</a>
        @endif
    </div>
</form>

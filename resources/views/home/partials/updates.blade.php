<section class="concept-updates">
    <div class="concept-updates__header">
        <h2>Latest at gFree</h2>
        <a href="/announcements">View all</a>
    </div>

    <div class="concept-updates__grid">
        @foreach ($updates as $update)
            <article>
                <p>{{ $update['type'] }}</p>
                <h3>{{ $update['title'] }}</h3>
                <span>{{ $update['summary'] }}</span>
                <a href="{{ $update['url'] }}" aria-label="Open {{ $update['title'] }}"></a>
            </article>
        @endforeach
    </div>
</section>

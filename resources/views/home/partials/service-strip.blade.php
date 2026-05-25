<section class="concept-service-strip" aria-label="Sunday details">
    @foreach ($serviceDetails as $detail)
        <div>
            <span>{{ $detail['label'] }}</span>
            <strong>{{ $detail['value'] }}</strong>
        </div>
    @endforeach
</section>

@php
    $background = $data['background'] ?? 'white';
    $imagePosition = $data['image_position'] ?? 'left';
@endphp

<section @class([
    'page-block',
    'page-block--image-text',
    'page-block--bg-' . $background,
    'page-block--image-right' => $imagePosition === 'right',
    'page-block--image-center' => $imagePosition === 'center',
    'page-block--image-full' => $imagePosition === 'full_width',
])>
    <div class="page-block__inner page-image-text">
        @if (filled($data['image_url'] ?? null))
            <img src="{{ $data['image_url'] }}" alt="{{ $data['image_alt'] ?? '' }}">
        @endif

        <div>
            @if (filled($data['eyebrow'] ?? null))
                <p class="page-block__eyebrow">{{ $data['eyebrow'] }}</p>
            @endif

            @if (filled($data['heading'] ?? null))
                <h2>{{ $data['heading'] }}</h2>
            @endif

            @if (filled($data['body'] ?? null))
                <div class="page-rich-text">
                    {!! $data['body'] !!}
                </div>
            @endif

            @if (filled($data['button_label'] ?? null) && filled($data['button_url'] ?? null))
                <a class="page-block__button" href="{{ $data['button_url'] }}">{{ $data['button_label'] }}</a>
            @endif
        </div>
    </div>
</section>

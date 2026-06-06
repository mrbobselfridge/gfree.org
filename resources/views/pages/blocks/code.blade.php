@php
    $code = $data['code'] ?? null;
    $contentWidth = match ($data['content_width'] ?? 'medium') {
        'small' => 'small',
        'wide', 'large' => 'wide',
        'full' => 'full',
        'none' => 'none',
        default => 'medium',
    };
    $background = $data['background'] ?? 'white';
@endphp

@if (filled($code))
    @if ($contentWidth === 'none')
        {!! $code !!}
    @else
        <section @class(['page-block', 'page-block--code', 'page-block--bg-' . $background])>
            <div @class([
                'page-block__inner',
                'page-block__inner--text-' . $contentWidth => $contentWidth !== 'full',
                'page-block__inner--full' => $contentWidth === 'full',
                'page-code-block',
            ])>
                {!! $code !!}
            </div>
        </section>
    @endif
@endif

@php($body = $data['body'] ?? null)

@if (filled($body))
    <section class="page-block page-block--header-message-box" aria-label="Header message">
        <div class="page-block__inner page-header-message-box__inner">
            <div class="ministry-hero-contact page-header-message-box">
                <div class="page-rich-text page-header-message-box__content">
                    {!! \App\Support\RichContent::render($body) !!}
                </div>
            </div>
        </div>
    </section>
@endif

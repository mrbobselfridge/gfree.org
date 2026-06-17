@if (count($contentBlocks))
    <div class="page-blocks">
        @foreach ($contentBlocks as $blockIndex => $block)
            @php($data = $block['data'] ?? [])

            @switch($block['type'])
                @case('text')
                    @include('pages.blocks.text')
                    @break

                @case('image_text')
                    @include('pages.blocks.image-text')
                    @break

                @case('process_steps')
                    @include('pages.blocks.process-steps')
                    @break

                @case('cta')
                    @include('pages.blocks.cta')
                    @break

                @case('link_cards')
                    @include('pages.blocks.link-cards')
                    @break

                @case('info_strip')
                    @include('pages.blocks.info-strip')
                    @break

                @case('embed')
                    @include('pages.blocks.embed')
                    @break

                @case('code')
                    @include('pages.blocks.code')
                    @break

                @case('related_content')
                    @include('pages.blocks.related-content')
                    @break

                @case('youtube_feed')
                    @include('pages.blocks.youtube-feed')
                    @break
            @endswitch
        @endforeach
    </div>
@endif

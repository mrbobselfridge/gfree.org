@if (count($contentBlocks))
    <div class="page-blocks">
        @foreach ($contentBlocks as $block)
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

                @case('announcements_bar')
                    @include('pages.blocks.announcements-bar')
                    @break
            @endswitch
        @endforeach
    </div>
@endif

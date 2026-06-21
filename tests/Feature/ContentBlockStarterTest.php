<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Pages\Pages\CreatePage;
use App\Filament\Admin\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContentBlockStarterTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_pages_start_with_an_open_text_content_block(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreatePage::class)
            ->assertSet('data.content_blocks', fn (array $blocks): bool => $this->hasOneStarterTextBlock($blocks))
            ->assertSee('YouTube Feed')
            ->assertSee('Child Cards');
    }

    public function test_edit_pages_without_content_blocks_do_not_get_new_starter_blocks(): void
    {
        $page = Page::query()->create([
            'title' => 'Blank Page',
            'slug' => 'blank-page',
            'content_blocks' => null,
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditPage::class, ['record' => $page->getKey()])
            ->assertSet('data.content_blocks', []);
    }

    public function test_page_youtube_feed_block_channel_url_fills_feed_url(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreatePage::class)
            ->set('data.content_blocks', [
                [
                    'type' => 'youtube_feed',
                    'data' => [
                        'youtube_channel_url' => null,
                        'youtube_feed_url' => null,
                    ],
                ],
            ])
            ->set('data.content_blocks.0.data.youtube_channel_url', 'https://www.youtube.com/channel/UCPageBlockChannelId/videos')
            ->assertSet('data.content_blocks.0.data.youtube_feed_url', 'https://www.youtube.com/feeds/videos.xml?channel_id=UCPageBlockChannelId');
    }

    public function test_page_content_block_name_is_editor_only_and_used_in_builder_label(): void
    {
        $page = Page::query()->create([
            'title' => 'Block Names',
            'slug' => 'block-names',
            'content_blocks' => [
                [
                    'type' => 'text',
                    'data' => [
                        'title' => 'Welcome editor block',
                        'heading' => 'Public heading',
                        'body' => '<p>Public body.</p>',
                        'background' => 'white',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditPage::class, ['record' => $page->getKey()])
            ->assertSee('Text - Welcome editor block');

        $this->get('/block-names')
            ->assertOk()
            ->assertSee('Public heading')
            ->assertSee('Public body.')
            ->assertDontSee('Welcome editor block');
    }

    private function hasOneStarterTextBlock(array $blocks): bool
    {
        $block = collect($blocks)->first();

        return count($blocks) === 1
            && ($block['type'] ?? null) === 'text'
            && ($block['data']['background'] ?? null) === 'white'
            && ($block['data']['content_width'] ?? null) === 'medium';
    }
}

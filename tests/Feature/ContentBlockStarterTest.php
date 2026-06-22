<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Pages\Pages\CreatePage;
use App\Filament\Admin\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\User;
use App\Support\ContentBlocks;
use Filament\Forms\Components\Textarea;
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

    public function test_related_content_auto_rotate_delay_defaults_when_empty_on_non_auto_layout(): void
    {
        $parent = Page::query()->create([
            'title' => 'Resources',
            'slug' => 'resources',
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Child Resource',
            'slug' => 'resources/child-resource',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(CreatePage::class)
            ->set('data.title', 'Links')
            ->set('data.slug', 'links')
            ->set('data.content_blocks', [
                [
                    'type' => 'related_content',
                    'data' => [
                        'enable_search' => true,
                        'display_mode' => ContentBlocks::RELATED_CONTENT_MODE_ALL,
                        'is_visible' => true,
                        'background' => 'white',
                        'content_width' => 'wide',
                        'layout' => ContentBlocks::RELATED_CONTENT_LAYOUT_CARD_GRID,
                        'associated_parent_page_id' => $parent->getKey(),
                        'item_limit' => ContentBlocks::RELATED_CONTENT_DEFAULT_LIMIT,
                        'sort_preset' => ContentBlocks::RELATED_CONTENT_SORT_TITLE_ASC,
                        'content_type' => ContentBlocks::RELATED_CONTENT_TYPE_PAGES,
                        'carousel_auto_delay_seconds' => null,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $block = Page::query()
            ->where('slug', 'links')
            ->firstOrFail()
            ->content_blocks[0]['data'];

        $this->assertSame(
            ContentBlocks::RELATED_CONTENT_DEFAULT_AUTO_DELAY_SECONDS,
            $block['carousel_auto_delay_seconds'] ?? null,
        );
    }

    public function test_public_content_block_textareas_use_html_code_highlighting(): void
    {
        $isHtmlCodeTextarea = function (Textarea $field): bool {
            $attributes = $field->getExtraInputAttributeBag()->getAttributes();

            return ($attributes['data-twyxtco-code-textarea'] ?? null) === 'true'
                && ($attributes['data-twyxtco-code-language'] ?? null) === 'html';
        };

        Livewire::actingAs(User::factory()->create())
            ->test(CreatePage::class)
            ->set('data.content_blocks', [
                [
                    'type' => 'process_steps',
                    'data' => [
                        'steps' => [
                            ['title' => 'Arrive', 'summary' => 'Meet us here.'],
                        ],
                    ],
                ],
                [
                    'type' => 'link_cards',
                    'data' => [
                        'cards' => [
                            ['title' => 'Serve', 'summary' => 'Find a team.'],
                        ],
                    ],
                ],
                [
                    'type' => 'info_strip',
                    'data' => [
                        'items' => [
                            ['label' => 'When', 'value' => 'Sundays at 10:30 AM'],
                        ],
                    ],
                ],
            ])
            ->assertFormFieldExists('content_blocks.0.data.steps.0.summary', $isHtmlCodeTextarea)
            ->assertFormFieldExists('content_blocks.1.data.cards.0.summary', $isHtmlCodeTextarea)
            ->assertFormFieldExists('content_blocks.2.data.items.0.value', $isHtmlCodeTextarea);
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

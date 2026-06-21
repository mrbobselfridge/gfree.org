<?php

namespace Tests\Feature;

use App\Filament\Admin\Forms\RichContentPlugins\HtmlSourcePlugin;
use App\Filament\Admin\Forms\RichEditorDefaults;
use App\Models\Page;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\CodeBlockAccess;
use App\Support\LinkCard;
use Filament\Forms\Components\RichEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodeBlockAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_code_blocks_permission_is_available_under_content_admin_areas(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]))
            ->get('/admin/users/create')
            ->assertOk()
            ->assertSee('Code Blocks');
    }

    public function test_admins_can_see_rich_text_source_tool(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]));

        $editor = RichEditorDefaults::configure(RichEditor::make('body'));

        $this->assertRichEditorHasSourceTool($editor);
    }

    public function test_editor_with_code_blocks_access_can_see_rich_text_source_tool(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [AdminAccess::PAGES, AdminAccess::CODE_BLOCKS],
                'records' => [],
            ],
        ]));

        $editor = RichEditorDefaults::configure(RichEditor::make('body'));

        $this->assertRichEditorHasSourceTool($editor);
    }

    public function test_editor_without_code_blocks_access_cannot_see_rich_text_source_tool(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [AdminAccess::PAGES],
                'records' => [],
            ],
        ]));

        $editor = RichEditorDefaults::configure(RichEditor::make('body'));
        $plugins = (fn (): array => $this->plugins)->call($editor);
        $toolbarButtons = (fn (): array => $this->toolbarButtons)->call($editor);

        $this->assertEmpty(array_filter(
            $plugins,
            fn (object $plugin): bool => $plugin instanceof HtmlSourcePlugin,
        ));
        $this->assertNotContains(HtmlSourcePlugin::TOOL, collect($toolbarButtons)->flatten()->all());
    }

    public function test_editor_without_code_blocks_access_cannot_change_existing_code_block_data(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [AdminAccess::PAGES],
                'records' => [],
            ],
        ]);

        $existingBlocks = [
            [
                'type' => 'text',
                'data' => [
                    'heading' => 'Existing text',
                    'background' => 'white',
                ],
            ],
            [
                'type' => 'code',
                'data' => [
                    'title' => 'Original code',
                    'background' => 'teal',
                    'content_width' => 'none',
                    'code' => '<script>window.originalCode = true;</script>',
                ],
            ],
        ];

        $submittedBlocks = [
            [
                'type' => 'code',
                'data' => [
                    'title' => 'Tampered code',
                    'background' => 'gold',
                    'content_width' => 'full',
                    'code' => '<script>window.tamperedCode = true;</script>',
                ],
            ],
            [
                'type' => 'text',
                'data' => [
                    'heading' => 'Moved text',
                    'background' => 'black',
                ],
            ],
            [
                'type' => 'code',
                'data' => [
                    'title' => 'New unauthorized code',
                    'background' => 'white',
                    'content_width' => 'none',
                    'code' => '<script>window.newUnauthorizedCode = true;</script>',
                ],
            ],
        ];

        $protectedBlocks = CodeBlockAccess::protectBlocks($submittedBlocks, $existingBlocks, $editor);

        $this->assertSame('code', $protectedBlocks[0]['type']);
        $this->assertSame('Original code', $protectedBlocks[0]['data']['title']);
        $this->assertSame('teal', $protectedBlocks[0]['data']['background']);
        $this->assertSame('none', $protectedBlocks[0]['data']['content_width']);
        $this->assertSame('<script>window.originalCode = true;</script>', $protectedBlocks[0]['data']['code']);
        $this->assertSame('text', $protectedBlocks[1]['type']);
        $this->assertSame('Moved text', $protectedBlocks[1]['data']['heading']);
        $this->assertCount(2, $protectedBlocks);
    }

    public function test_editor_with_code_blocks_access_can_save_code_block_data(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [AdminAccess::PAGES, AdminAccess::CODE_BLOCKS],
                'records' => [],
            ],
        ]);

        $submittedBlocks = [
            [
                'type' => 'code',
                'data' => [
                    'title' => 'Editable code',
                    'background' => 'gold',
                    'content_width' => 'full',
                    'code' => '<div id="editable-code"></div>',
                ],
            ],
        ];

        $this->assertSame($submittedBlocks, CodeBlockAccess::protectBlocks($submittedBlocks, [], $editor));
    }

    public function test_editor_without_code_blocks_access_can_open_content_with_existing_code_block(): void
    {
        $page = Page::query()->create([
            'title' => 'Interactive Page',
            'slug' => 'interactive-page',
            'content_blocks' => [
                [
                    'type' => 'code',
                    'data' => [
                        'title' => 'Existing embed',
                        'background' => 'white',
                        'content_width' => 'none',
                        'code' => '<script>window.existingEmbed = true;</script>',
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [AdminAccess::PAGES],
                'records' => [],
            ],
        ]);

        $this->actingAs($editor)
            ->get("/admin/pages/{$page->getKey()}/edit")
            ->assertOk()
            ->assertSee('Existing embed')
            ->assertSee('window.existingEmbed', false)
            ->assertDontSee('Code Blocks');
    }

    public function test_editor_without_code_blocks_access_cannot_change_existing_code_card_data(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [AdminAccess::PAGES],
                'records' => [],
            ],
        ]);

        $existingBlocks = [
            [
                'type' => 'link_cards',
                'data' => [
                    'heading' => 'Cards',
                    'cards' => [
                        [
                            'key' => 'safe-link',
                            'title' => 'Safe link',
                            'type' => LinkCard::TYPE_LINK_SAME,
                            'url' => '/safe',
                        ],
                        [
                            'key' => 'safe-image',
                            'title' => 'Safe image',
                            'type' => LinkCard::TYPE_FLIP_IMAGE,
                            'image_path' => 'pages/content-images/original.jpg',
                            'image_fit' => 'cover',
                        ],
                        [
                            'key' => 'flip-card',
                            'title' => 'Original flip',
                            'summary' => 'Original summary',
                            'type' => LinkCard::TYPE_FLIP_HTML,
                            'html' => '<strong>Original HTML</strong>',
                        ],
                    ],
                ],
            ],
        ];

        $submittedBlocks = [
            [
                'type' => 'link_cards',
                'data' => [
                    'heading' => 'Cards edited',
                    'cards' => [
                        [
                            'key' => 'safe-link',
                            'title' => 'Safe link edited',
                            'type' => LinkCard::TYPE_LINK_NEW,
                            'url' => '/safe.pdf',
                            'html' => '<script>window.shouldNotSave = true;</script>',
                        ],
                        [
                            'key' => 'flip-card',
                            'title' => 'Tampered flip',
                            'type' => LinkCard::TYPE_FLIP_HTML,
                            'html' => '<strong>Tampered HTML</strong>',
                        ],
                        [
                            'key' => 'safe-image',
                            'title' => 'Safe image edited',
                            'type' => LinkCard::TYPE_FLIP_IMAGE,
                            'image_path' => 'pages/content-images/updated.jpg',
                            'image_alt' => 'Updated image',
                            'image_fit' => 'contain',
                            'image_focus' => 'bottom',
                            'image_zoom' => 130,
                            'html' => '<script>window.shouldAlsoNotSave = true;</script>',
                        ],
                        [
                            'key' => 'new-widget',
                            'title' => 'Unauthorized widget',
                            'type' => LinkCard::TYPE_JAVASCRIPT_WIDGET,
                            'javascript' => 'window.unauthorizedWidget = true;',
                        ],
                    ],
                ],
            ],
        ];

        $protectedBlocks = CodeBlockAccess::protectBlocks($submittedBlocks, $existingBlocks, $editor);
        $cards = $protectedBlocks[0]['data']['cards'];

        $this->assertSame('Safe link edited', $cards[0]['title']);
        $this->assertSame(LinkCard::TYPE_LINK_NEW, $cards[0]['type']);
        $this->assertArrayNotHasKey('html', $cards[0]);
        $this->assertSame('Original flip', $cards[1]['title']);
        $this->assertSame(LinkCard::TYPE_FLIP_HTML, $cards[1]['type']);
        $this->assertSame('<strong>Original HTML</strong>', $cards[1]['html']);
        $this->assertSame('Safe image edited', $cards[2]['title']);
        $this->assertSame(LinkCard::TYPE_FLIP_IMAGE, $cards[2]['type']);
        $this->assertSame('pages/content-images/updated.jpg', $cards[2]['image_path']);
        $this->assertSame('Updated image', $cards[2]['image_alt']);
        $this->assertSame('contain', $cards[2]['image_fit']);
        $this->assertSame('bottom', $cards[2]['image_focus']);
        $this->assertSame(130, $cards[2]['image_zoom']);
        $this->assertArrayNotHasKey('html', $cards[2]);
        $this->assertCount(3, $cards);
    }

    public function test_code_card_type_options_are_hidden_without_code_blocks_access(): void
    {
        $page = Page::query()->create([
            'title' => 'Cards Page',
            'slug' => 'cards-page',
            'content_blocks' => [
                [
                    'type' => 'link_cards',
                    'data' => [
                        'heading' => 'Cards',
                        'cards' => [
                            [
                                'key' => 'card-one',
                                'title' => 'Plain card',
                                'type' => LinkCard::TYPE_DISPLAY,
                            ],
                        ],
                    ],
                ],
            ],
            'is_published' => true,
        ]);

        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [AdminAccess::PAGES],
                'records' => [],
            ],
        ]);

        $this->actingAs($editor)
            ->get("/admin/pages/{$page->getKey()}/edit")
            ->assertOk()
            ->assertSee('Flip card with image back')
            ->assertDontSee('Flip card with HTML back')
            ->assertDontSee('JavaScript widget');

        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]))
            ->get("/admin/pages/{$page->getKey()}/edit")
            ->assertOk()
            ->assertSee('Flip card with image back')
            ->assertSee('Flip card with HTML back')
            ->assertSee('JavaScript widget');
    }

    private function assertRichEditorHasSourceTool(RichEditor $editor): void
    {
        $plugins = (fn (): array => $this->plugins)->call($editor);
        $toolbarButtons = (fn (): array => $this->toolbarButtons)->call($editor);

        $sourcePlugins = array_filter(
            $plugins,
            fn (object $plugin): bool => $plugin instanceof HtmlSourcePlugin,
        );

        $this->assertNotEmpty($sourcePlugins);
        $this->assertContainsOnlyInstancesOf(HtmlSourcePlugin::class, $sourcePlugins);
        $this->assertContains(HtmlSourcePlugin::TOOL, collect($toolbarButtons)->flatten()->all());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\CodeBlockAccess;
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
}

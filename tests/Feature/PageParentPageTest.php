<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Pages\Pages\CreatePage;
use App\Filament\Admin\Resources\Pages\Pages\EditPage;
use App\Filament\Admin\Resources\Pages\Pages\ListPages;
use App\Filament\Admin\Resources\Pages\Schemas\PageForm;
use App\Models\Page;
use App\Models\User;
use Filament\Schemas\Components\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PageParentPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_sort_order_can_be_saved_from_the_admin_form(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreatePage::class)
            ->set('data.title', 'Sorted Page')
            ->set('data.slug', 'sorted-page')
            ->set('data.sort_order', 17)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(Page::class, [
            'title' => 'Sorted Page',
            'slug' => 'sorted-page',
            'sort_order' => 17,
        ]);
    }

    public function test_page_form_groups_fields_into_collapsible_sections(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreatePage::class)
            ->assertFormFieldVisible('title')
            ->assertFormFieldVisible('is_published')
            ->assertFormFieldVisible('is_redirect')
            ->assertSchemaComponentExists('pages-section-controls')
            ->assertSchemaComponentHidden('pages-redirect')
            ->assertSchemaComponentExists(
                'pages-display',
                checkComponentUsing: fn (Section $component): bool => $this->isOpenCreatePageSection($component),
            )
            ->assertSchemaComponentExists(
                'pages-content-blocks',
                checkComponentUsing: fn (Section $component): bool => $this->isOpenCreatePageSection($component),
            )
            ->assertSchemaComponentExists(
                'pages-settings',
                checkComponentUsing: fn (Section $component): bool => $this->isOpenCreatePageSection($component),
            )
            ->assertFormFieldVisible('show_site_chrome')
            ->assertFormFieldVisible('show_page_header')
            ->assertFormFieldVisible('hero_label')
            ->assertFormFieldVisible('intro')
            ->assertFormFieldVisible('hero_image_path')
            ->assertFormFieldVisible('card_image_path')
            ->assertFormFieldVisible('slug')
            ->assertFormFieldVisible('sort_order')
            ->assertFormFieldVisible('publish_at')
            ->assertFormFieldVisible('expires_at')
            ->assertFormFieldVisible('seo_title')
            ->assertFormFieldVisible('seo_description')
            ->assertFormFieldVisible('parent_page_id')
            ->assertSee('Collapse all')
            ->assertSee('Expand all');
    }

    public function test_edit_page_form_defaults_content_open_and_settings_display_closed(): void
    {
        $page = Page::query()->create([
            'title' => 'Contact',
            'slug' => 'contact',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditPage::class, ['record' => $page->getKey()])
            ->assertSchemaComponentExists(
                'pages-settings',
                checkComponentUsing: fn (Section $component): bool => $this->isPersistedEditPageSection($component)
                    && $component->isCollapsed(),
            )
            ->assertSchemaComponentExists(
                'pages-display',
                checkComponentUsing: fn (Section $component): bool => $this->isPersistedEditPageSection($component)
                    && $component->isCollapsed(),
            )
            ->assertSchemaComponentExists(
                'pages-content-blocks',
                checkComponentUsing: fn (Section $component): bool => $this->isPersistedEditPageSection($component)
                    && ! $component->isCollapsed(),
            )
            ->assertSchemaComponentHidden('pages-redirect');
    }

    public function test_redirect_pages_show_redirect_section_and_hide_normal_page_sections(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreatePage::class)
            ->set('data.is_redirect', true)
            ->assertSchemaComponentExists(
                'pages-redirect',
                checkComponentUsing: fn (Section $component): bool => $component->isCollapsible()
                    && ! $component->isCollapsed()
                    && ! $component->shouldPersistCollapsed(),
            )
            ->assertSchemaComponentHidden('pages-display')
            ->assertSchemaComponentHidden('pages-content-blocks')
            ->assertSchemaComponentVisible('pages-settings')
            ->assertFormFieldVisible('redirect_url')
            ->assertFormFieldVisible('redirect_status_code')
            ->assertFormFieldVisible('slug')
            ->assertFormFieldVisible('sort_order')
            ->assertFormFieldVisible('publish_at')
            ->assertFormFieldVisible('expires_at')
            ->assertFormFieldHidden('show_site_chrome')
            ->assertFormFieldHidden('seo_title')
            ->assertFormFieldHidden('seo_description')
            ->assertFormFieldHidden('parent_page_id');
    }

    public function test_pages_table_defaults_to_title_order(): void
    {
        $zebra = Page::query()->create([
            'title' => 'Zebra Page',
            'slug' => 'zebra-page',
            'sort_order' => 10,
            'is_published' => true,
        ]);

        $alpha = Page::query()->create([
            'title' => 'Alpha Page',
            'slug' => 'alpha-page',
            'sort_order' => 20,
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListPages::class)
            ->assertCanSeeTableRecords([$alpha, $zebra], inOrder: true);
    }

    public function test_pages_table_sort_search_and_column_visibility_preferences_are_session_persisted(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ListPages::class)
            ->assertTableColumnExists('title', fn ($column): bool => $column->isSortable() && $column->isGloballySearchable())
            ->assertTableColumnExists('is_published', fn ($column): bool => $column->isSortable() && $column->isGloballySearchable())
            ->assertTableColumnExists('slug', fn ($column): bool => $column->isSortable() && $column->isGloballySearchable())
            ->assertTableColumnExists('is_redirect', fn ($column): bool => $column->isSortable() && $column->isGloballySearchable())
            ->assertTableColumnExists('publish_at', fn ($column): bool => $column->isSortable() && $column->isGloballySearchable())
            ->assertTableColumnExists('expires_at', fn ($column): bool => $column->isSortable() && $column->isGloballySearchable())
            ->assertTableColumnExists('sort_order', fn ($column): bool => $column->isSortable())
            ->tap(function ($component): void {
                $table = $component->instance()->getTable();

                $this->assertSame('title', $table->getDefaultSortColumn());
                $this->assertTrue($table->persistsSortInSession());
                $this->assertTrue($table->persistsColumnsInSession());
            });
    }

    public function test_pages_table_searches_live_and_type_labels(): void
    {
        $livePage = Page::query()->create([
            'title' => 'Alpha',
            'slug' => 'alpha',
            'is_published' => true,
        ]);

        $draftPage = Page::query()->create([
            'title' => 'Beta',
            'slug' => 'beta',
            'is_published' => false,
        ]);

        $redirectPage = Page::query()->create([
            'title' => 'Gamma',
            'slug' => 'gamma',
            'is_published' => true,
            'is_redirect' => true,
            'redirect_url' => '/alpha',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListPages::class)
            ->searchTable('live')
            ->assertCanSeeTableRecords([$livePage, $redirectPage])
            ->assertCanNotSeeTableRecords([$draftPage])
            ->searchTable('redirect')
            ->assertCanSeeTableRecords([$redirectPage])
            ->assertCanNotSeeTableRecords([$livePage, $draftPage])
            ->searchTable('page')
            ->assertCanSeeTableRecords([$livePage, $draftPage])
            ->assertCanNotSeeTableRecords([$redirectPage]);
    }

    public function test_page_can_belong_to_another_page(): void
    {
        $parent = Page::query()->create([
            'title' => 'About',
            'slug' => 'about',
            'is_published' => true,
        ]);

        $child = Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Beliefs',
            'slug' => 'about/beliefs',
            'hero_label' => 'What We Believe',
            'intro' => 'Core beliefs and doctrine.',
            'is_published' => true,
        ]);

        $this->assertTrue($child->parentPage->is($parent));
        $this->assertTrue($parent->childPages()->first()->is($child));
    }

    public function test_parent_page_options_include_active_and_inactive_page_labels(): void
    {
        $target = Page::query()->create([
            'title' => 'Target',
            'slug' => 'target',
            'is_published' => true,
        ]);

        $active = Page::query()->create([
            'title' => 'Active Parent',
            'slug' => 'active-parent',
            'sort_order' => 20,
            'is_published' => true,
        ]);

        $inactive = Page::query()->create([
            'title' => 'Inactive Parent',
            'slug' => 'inactive-parent',
            'sort_order' => 10,
            'is_published' => false,
        ]);

        $options = PageForm::parentPageOptions($target);

        $this->assertArrayNotHasKey((string) $target->getKey(), $options);
        $this->assertSame([
            $inactive->getKey(),
            $active->getKey(),
        ], array_keys($options));
        $this->assertSame('Active Parent (/active-parent) - Active', $options[(string) $active->getKey()]);
        $this->assertSame('Inactive Parent (/inactive-parent) - Inactive', $options[(string) $inactive->getKey()]);
    }

    public function test_edit_form_shows_direct_child_pages(): void
    {
        $parent = Page::query()->create([
            'title' => 'About',
            'slug' => 'about',
            'is_published' => true,
        ]);

        $child = Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Beliefs',
            'slug' => 'about/beliefs',
            'hero_label' => 'What We Believe',
            'intro' => 'Core beliefs and doctrine.',
            'sort_order' => 20,
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $child->getKey(),
            'title' => 'Membership Class',
            'slug' => 'about/beliefs/membership',
            'is_published' => false,
        ]);

        Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Draft Child',
            'slug' => 'about/draft-child',
            'sort_order' => 10,
            'is_published' => false,
        ]);

        $content = (string) PageForm::directChildPagesContent($parent);

        $this->assertStringContainsString('Draft Child', $content);
        $this->assertStringContainsString('Beliefs', $content);
        $this->assertLessThan(
            strpos($content, 'Beliefs'),
            strpos($content, 'Draft Child'),
        );
        $this->assertStringContainsString('/about/beliefs', $content);
        $this->assertStringContainsString('title="Active"', $content);
        $this->assertStringContainsString('title="Inactive"', $content);
        $this->assertStringContainsString('title="View page"', $content);
        $this->assertStringContainsString('title="Edit page"', $content);
        $this->assertStringContainsString('align-items: center;', $content);
        $this->assertStringContainsString('gap: .04rem;', $content);
        $this->assertStringContainsString('color: #9ca3af;', $content);
        $this->assertStringContainsString('color: #f59e0b;', $content);
        $this->assertStringContainsString('color: #22c55e;', $content);
        $this->assertStringContainsString('color: #ef4444;', $content);
        $this->assertStringContainsString('width: 1.5rem; height: 1.5rem;', $content);
        $this->assertStringContainsString('max-width: 1rem; max-height: 1rem;', $content);
        $this->assertStringContainsString('Small label: What We Believe', $content);
        $this->assertStringContainsString('Intro: Core beliefs and doctrine.', $content);
        $this->assertStringNotContainsString('Membership Class', $content);
    }

    public function test_page_cannot_use_itself_as_parent(): void
    {
        $page = Page::query()->create([
            'title' => 'About',
            'slug' => 'about',
            'is_published' => true,
        ]);

        $this->expectException(ValidationException::class);

        $page->parent_page_id = $page->getKey();
        $page->save();
    }

    public function test_page_cannot_use_descendant_as_parent(): void
    {
        $parent = Page::query()->create([
            'title' => 'About',
            'slug' => 'about',
            'is_published' => true,
        ]);

        $child = Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Beliefs',
            'slug' => 'about/beliefs',
            'is_published' => true,
        ]);

        $grandchild = Page::query()->create([
            'parent_page_id' => $child->getKey(),
            'title' => 'Membership Class',
            'slug' => 'about/beliefs/membership',
            'is_published' => true,
        ]);

        $this->expectException(ValidationException::class);

        $parent->parent_page_id = $grandchild->getKey();
        $parent->save();
    }

    public function test_edit_page_form_rejects_descendant_parent_selection(): void
    {
        $parent = Page::query()->create([
            'title' => 'About',
            'slug' => 'about',
            'is_published' => true,
        ]);

        $child = Page::query()->create([
            'parent_page_id' => $parent->getKey(),
            'title' => 'Beliefs',
            'slug' => 'about/beliefs',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditPage::class, ['record' => $parent->getKey()])
            ->set('data.parent_page_id', $child->getKey())
            ->call('save')
            ->assertHasFormErrors(['parent_page_id']);
    }

    private function isOpenCreatePageSection(Section $component): bool
    {
        return $component->isCollapsible()
            && ! $component->isCollapsed()
            && ! $component->shouldPersistCollapsed();
    }

    private function isPersistedEditPageSection(Section $component): bool
    {
        return $component->isCollapsible()
            && $component->shouldPersistCollapsed();
    }
}

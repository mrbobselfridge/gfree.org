<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Pages\Pages\EditPage;
use App\Filament\Admin\Resources\Pages\Schemas\PageForm;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PageParentPageTest extends TestCase
{
    use RefreshDatabase;

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
            'is_published' => true,
        ]);

        $inactive = Page::query()->create([
            'title' => 'Inactive Parent',
            'slug' => 'inactive-parent',
            'is_published' => false,
        ]);

        $options = PageForm::parentPageOptions($target);

        $this->assertArrayNotHasKey((string) $target->getKey(), $options);
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
            'is_published' => true,
        ]);

        Page::query()->create([
            'parent_page_id' => $child->getKey(),
            'title' => 'Membership Class',
            'slug' => 'about/beliefs/membership',
            'is_published' => false,
        ]);

        $content = (string) PageForm::directChildPagesContent($parent);

        $this->assertStringContainsString('Beliefs', $content);
        $this->assertStringContainsString('/about/beliefs', $content);
        $this->assertStringContainsString('title="Active"', $content);
        $this->assertStringContainsString('title="View page"', $content);
        $this->assertStringContainsString('title="Edit page"', $content);
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
}

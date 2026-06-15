<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\FileDocuments\Pages\CreateFileDocument;
use App\Filament\Admin\Resources\FileDocuments\Pages\EditFileDocument;
use App\Filament\Admin\Resources\Pages\Pages\CreatePage;
use App\Filament\Admin\Resources\Pages\Pages\EditPage;
use App\Models\FileDocument;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SlugAutoUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_slug_auto_updates_on_create_but_not_edit(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreatePage::class)
            ->set('data.title', 'New Visitor Page')
            ->assertSet('data.slug', 'new-visitor-page');

        $page = Page::query()->create([
            'title' => 'Original Page',
            'slug' => 'stable-page-url',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditPage::class, ['record' => $page->getKey()])
            ->set('data.title', 'Changed Page Title')
            ->assertSet('data.slug', 'stable-page-url')
            ->assertFormComponentActionExists('slug', 'rebuildSlug')
            ->assertFormComponentActionHasLabel('slug', 'rebuildSlug', 'Generate path')
            ->callFormComponentAction('slug', 'rebuildSlug')
            ->assertSet('data.slug', 'changed-page-title');
    }

    public function test_file_library_slug_can_be_rebuilt_from_category_and_title(): void
    {
        $document = FileDocument::query()->create([
            'title' => 'Original File',
            'file_name' => 'stable-file-url',
            'category' => 'Form',
            'visibility' => FileDocument::VISIBILITY_PUBLIC,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditFileDocument::class, ['record' => $document->getKey()])
            ->set('data.title', 'Changed File Title')
            ->assertSet('data.file_name', 'stable-file-url')
            ->assertFormComponentActionExists('file_name', 'rebuildFileName')
            ->assertFormComponentActionHasLabel('file_name', 'rebuildFileName', 'Generate path')
            ->callFormComponentAction('file_name', 'rebuildFileName')
            ->assertSet('data.file_name', 'form-changed-file-title');
    }

    public function test_file_library_slug_defaults_from_category_and_title_on_create(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateFileDocument::class)
            ->set('data.category', 'Bulletin')
            ->set('data.title', 'Sunday Worship Guide')
            ->assertSet('data.file_name', 'bulletin-sunday-worship-guide');
    }
}

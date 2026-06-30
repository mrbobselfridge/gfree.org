<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\NavigationLinks\NavigationLinkResource;
use App\Filament\Admin\Resources\NavigationLinks\Pages\CreateNavigationLink;
use App\Filament\Admin\Resources\NavigationLinks\Pages\ListNavigationLinks;
use App\Models\FileDocument;
use App\Models\FileDocumentVersion;
use App\Models\MediaImageMetadata;
use App\Models\NavigationLink;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\NavigationDestinationSuggestions;
use Filament\Forms\Components\TextInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class NavigationLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_navigation_respects_publish_and_expiration_dates(): void
    {
        NavigationLink::query()->create([
            'label' => 'Always Active',
            'url' => '/always-active',
            'location' => 'header',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'label' => 'Active Window',
            'url' => '/active-window',
            'location' => 'header',
            'sort_order' => 2,
            'publish_at' => now()->subHour(),
            'expires_at' => now()->addHour(),
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'label' => 'Future Link',
            'url' => '/future-link',
            'location' => 'header',
            'sort_order' => 3,
            'publish_at' => now()->addDay(),
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'label' => 'Expired Link',
            'url' => '/expired-link',
            'location' => 'header',
            'sort_order' => 4,
            'expires_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Always Active')
            ->assertSee('Active Window')
            ->assertDontSee('Future Link')
            ->assertDontSee('Expired Link');
    }

    public function test_header_navigation_renders_active_child_links_in_dropdowns(): void
    {
        $parent = NavigationLink::query()->create([
            'label' => 'Resources',
            'url' => '/resources',
            'location' => 'header',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'parent_id' => $parent->id,
            'label' => 'Kids',
            'url' => '/resources/kids',
            'location' => 'header',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'parent_id' => $parent->id,
            'label' => 'Future Students',
            'url' => '/resources/students',
            'location' => 'header',
            'sort_order' => 2,
            'publish_at' => now()->addDay(),
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-nav-toggle', false)
            ->assertSee('aria-controls="primary-navigation"', false)
            ->assertSee('data-subnav-toggle', false)
            ->assertSee('aria-controls="primary-navigation-submenu-0"', false)
            ->assertSee('data-subnav-panel', false)
            ->assertSee('Resources')
            ->assertSee('Kids')
            ->assertDontSee('Future Students');
    }

    public function test_utility_navigation_renders_above_existing_header_navigation(): void
    {
        $parent = NavigationLink::query()->create([
            'label' => 'Resources',
            'url' => '/resources',
            'location' => NavigationLink::LOCATION_HEADER,
            'sort_order' => 1,
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'parent_id' => $parent->id,
            'label' => 'Kids',
            'url' => '/resources/kids',
            'location' => NavigationLink::LOCATION_HEADER,
            'sort_order' => 1,
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'label' => 'Contact',
            'url' => '/contact',
            'location' => NavigationLink::LOCATION_UTILITY,
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('site-sticky-chrome', false)
            ->assertSee('site-utility-bar', false)
            ->assertSeeInOrder(['site-utility-bar', 'Contact', 'concept-header'])
            ->assertSee('data-subnav-toggle', false)
            ->assertSee('Resources')
            ->assertSee('Kids');
    }

    public function test_utility_bar_renders_social_links_allowed_for_utility_nav(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'facebook_url' => 'https://facebook.example/twyxtco',
            'instagram_url' => 'https://instagram.example/twyxtco',
            'youtube_url' => 'https://youtube.example/twyxtco',
            'social_link_placements' => [
                'facebook_url' => SiteSetting::SOCIAL_LINK_PLACEMENT_UTILITY,
                'instagram_url' => SiteSetting::SOCIAL_LINK_PLACEMENT_FOOTER,
                'youtube_url' => SiteSetting::SOCIAL_LINK_PLACEMENT_BOTH,
            ],
            'additional_social_links' => [
                [
                    'label' => 'Podcast',
                    'url' => 'https://podcast.example/twyxtco',
                    'placement' => SiteSetting::SOCIAL_LINK_PLACEMENT_UTILITY,
                    'image_path' => 'site-settings/additional-links/podcast.png',
                ],
                [
                    'label' => 'Blog',
                    'url' => 'https://blog.example/twyxtco',
                    'placement' => SiteSetting::SOCIAL_LINK_PLACEMENT_FOOTER,
                    'image_path' => 'site-settings/additional-links/blog.png',
                ],
                [
                    'label' => 'Store',
                    'url' => 'https://store.example/twyxtco',
                    'placement' => SiteSetting::SOCIAL_LINK_PLACEMENT_BOTH,
                    'image_path' => 'site-settings/additional-links/store.png',
                ],
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('site-utility-bar__social', false)
            ->assertSeeInOrder(['aria-label="Facebook"', 'aria-label="YouTube"', 'aria-label="Podcast"', 'aria-label="Store"'], false)
            ->assertSee('site-utility-social-link--facebook', false)
            ->assertDontSee('site-utility-social-link--instagram', false)
            ->assertSee('site-utility-social-link--youtube', false)
            ->assertSee('viewBox="0 0 24 24"', false)
            ->assertSee('site-utility-social-link--custom', false)
            ->assertSee('aria-label="Podcast"', false)
            ->assertSee('/storage/site-settings/additional-links/podcast.png', false)
            ->assertDontSee('class="site-utility-social-link site-utility-social-link--custom" href="https://blog.example/twyxtco"', false)
            ->assertSee('class="site-utility-social-link site-utility-social-link--custom" href="https://store.example/twyxtco"', false);
    }

    public function test_header_navigation_hides_links_to_inactive_matching_pages(): void
    {
        Page::query()->create([
            'title' => 'Visible Page',
            'slug' => 'visible-page',
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Absolute Visible Page',
            'slug' => 'absolute-visible-page',
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Future Page',
            'slug' => 'future-page',
            'publish_at' => now()->addDay(),
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Expired Page',
            'slug' => 'expired-page',
            'expires_at' => now()->subDay(),
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'is_published' => false,
        ]);

        foreach ([
            ['Visible Page Link', '/visible-page'],
            ['Absolute Visible Page Link', url('/absolute-visible-page')],
            ['Future Page Link', '/future-page'],
            ['Expired Page Link', '/expired-page'],
            ['Draft Page Link', '/draft-page'],
            ['System Route Link', '/announcements'],
            ['External Link', 'https://example.com/events'],
        ] as $index => [$label, $url]) {
            NavigationLink::query()->create([
                'label' => $label,
                'url' => $url,
                'location' => 'header',
                'sort_order' => $index + 1,
                'is_published' => true,
            ]);
        }

        $this->get('/')
            ->assertOk()
            ->assertSee('Visible Page Link')
            ->assertSee('Absolute Visible Page Link')
            ->assertSee('System Route Link')
            ->assertSee('External Link')
            ->assertDontSee('Future Page Link')
            ->assertDontSee('Expired Page Link')
            ->assertDontSee('Draft Page Link');
    }

    public function test_header_navigation_hides_child_links_to_inactive_matching_pages(): void
    {
        Page::query()->create([
            'title' => 'Kids Page',
            'slug' => 'kids-page',
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Future Students Page',
            'slug' => 'future-students-page',
            'publish_at' => now()->addDay(),
            'is_published' => true,
        ]);

        $parent = NavigationLink::query()->create([
            'label' => 'Resources',
            'url' => '/resources',
            'location' => 'header',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'parent_id' => $parent->id,
            'label' => 'Kids Page Link',
            'url' => '/kids-page',
            'location' => 'header',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'parent_id' => $parent->id,
            'label' => 'Future Students Page Link',
            'url' => '/future-students-page',
            'location' => 'header',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Resources')
            ->assertSee('Kids Page Link')
            ->assertDontSee('Future Students Page Link');
    }

    public function test_navigation_admin_shows_page_limit_statuses(): void
    {
        Page::query()->create([
            'title' => 'Live Page',
            'slug' => 'live-page',
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Windowed Page',
            'slug' => 'windowed-page',
            'publish_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Future Page',
            'slug' => 'future-page',
            'publish_at' => now()->addDay(),
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Expired Page',
            'slug' => 'expired-page',
            'expires_at' => now()->subDay(),
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'is_published' => false,
        ]);

        foreach ([
            ['Live Page Link', '/live-page'],
            ['Windowed Page Link', '/windowed-page'],
            ['Future Page Link', '/future-page'],
            ['Expired Page Link', '/expired-page'],
            ['Draft Page Link', '/draft-page'],
            ['Listing Link', '/announcements'],
        ] as $index => [$label, $url]) {
            NavigationLink::query()->create([
                'label' => $label,
                'url' => $url,
                'location' => 'header',
                'sort_order' => $index + 1,
                'is_published' => true,
            ]);
        }

        Livewire::actingAs(User::factory()->create())
            ->test(ListNavigationLinks::class)
            ->assertSee('Page live')
            ->assertSee('Page window active')
            ->assertSee('Hidden: page future')
            ->assertSee('Hidden: page expired')
            ->assertSee('Hidden: page draft')
            ->assertSee('No Page record uses /announcements');
    }

    public function test_navigation_destination_suggestions_include_live_pages_files_and_media(): void
    {
        Storage::fake('public');

        Page::query()->create([
            'title' => 'New Here',
            'slug' => 'new-here',
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'is_published' => false,
        ]);

        $this->documentWithVersion('Bulletin Guide', 'bulletin-guide', FileDocument::VISIBILITY_PUBLIC);
        $this->documentWithVersion('Internal Guide', 'internal-guide', FileDocument::VISIBILITY_PRIVATE);

        Storage::disk('public')->put('media-library/worship-banner.jpg', 'image');
        MediaImageMetadata::query()->create([
            'path' => 'media-library/worship-banner.jpg',
            'title' => 'Worship Banner',
            'tags' => ['Worship'],
        ]);

        $allSuggestions = NavigationDestinationSuggestions::optionValues();

        $this->assertContains('/new-here', $allSuggestions);
        $this->assertContains('/files/bulletin-guide', $allSuggestions);
        $this->assertContains('/storage/media-library/worship-banner.jpg', $allSuggestions);
        $this->assertNotContains('/draft-page', $allSuggestions);
        $this->assertNotContains('/files/internal-guide', $allSuggestions);

        $this->assertSame(['/storage/media-library/worship-banner.jpg'], NavigationDestinationSuggestions::optionValues('/worship'));
        $this->assertSame(['/files/bulletin-guide'], NavigationDestinationSuggestions::optionValues('/bulletin'));
    }

    public function test_navigation_destination_field_uses_destination_suggestions(): void
    {
        Page::query()->create([
            'title' => 'New Here',
            'slug' => 'new-here',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(CreateNavigationLink::class)
            ->assertFormFieldExists('url', fn (TextInput $field): bool => in_array(
                '/new-here',
                $field->getDatalistOptions() ?? [],
                true,
            ));
    }

    public function test_header_navigation_does_not_render_default_give_link(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('concept-nav__give', false)
            ->assertDontSee('>Give</a>', false);
    }

    public function test_header_navigation_is_not_limited_to_five_links(): void
    {
        foreach (range(1, 6) as $index) {
            NavigationLink::query()->create([
                'label' => "Header Link {$index}",
                'url' => "/header-link-{$index}",
                'location' => 'header',
                'sort_order' => $index,
                'is_published' => true,
            ]);
        }

        $this->get('/')
            ->assertOk()
            ->assertSee('Header Link 1')
            ->assertSee('Header Link 5')
            ->assertSee('Header Link 6');
    }

    public function test_navigation_links_table_has_requested_sortable_columns_and_label_edit_link(): void
    {
        $parent = NavigationLink::query()->create([
            'label' => 'Resources',
            'url' => '/resources',
            'location' => 'header',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $child = NavigationLink::query()->create([
            'parent_id' => $parent->id,
            'label' => 'RightNow Media',
            'url' => 'https://www.rightnowmedia.org/',
            'location' => 'header',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $expectedEditUrl = NavigationLinkResource::getUrl('edit', ['record' => $child]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListNavigationLinks::class)
            ->assertTableColumnExists('label', fn ($column) => $column->isSortable()
                && $column->isGloballySearchable()
                && $column->getUrl() === $expectedEditUrl, $child)
            ->assertTableColumnExists('url', fn ($column): bool => $column->isSortable()
                && $column->isGloballySearchable()
                && $column->getLabel() === 'Destination')
            ->assertTableColumnExists('parent.label', fn ($column): bool => $column->isSortable()
                && $column->isGloballySearchable())
            ->assertTableColumnExists('is_published', fn ($column): bool => $column->isSortable())
            ->tap(function ($component): void {
                $columns = $component->instance()->getTable()->getColumns();

                $this->assertSame(['label', 'url', 'location', 'parent.label'], array_slice(array_keys($columns), 0, 4));
            });
    }

    public function test_navigation_links_table_sorts_by_parent_label(): void
    {
        $resources = NavigationLink::query()->create([
            'label' => 'Resources',
            'url' => '/resources',
            'location' => 'header',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $members = NavigationLink::query()->create([
            'label' => 'Members',
            'url' => '/members',
            'location' => 'header',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $topLevel = NavigationLink::query()->create([
            'label' => 'New Here',
            'url' => '/new-here',
            'location' => 'header',
            'sort_order' => 3,
            'is_published' => true,
        ]);

        $memberChild = NavigationLink::query()->create([
            'parent_id' => $members->id,
            'label' => 'OneChurch',
            'url' => 'https://example.com/onechurch',
            'location' => 'header',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $resourceChild = NavigationLink::query()->create([
            'parent_id' => $resources->id,
            'label' => 'RightNow Media',
            'url' => 'https://example.com/rightnow-media',
            'location' => 'header',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListNavigationLinks::class)
            ->sortTable('parent.label', 'asc')
            ->assertCanSeeTableRecords([$topLevel, $memberChild, $resourceChild], inOrder: true);
    }

    public function test_navigation_links_table_published_icon_toggles_link_status(): void
    {
        $link = NavigationLink::query()->create([
            'label' => 'OneChurch',
            'url' => 'https://example.com/onechurch',
            'location' => 'header',
            'sort_order' => 1,
            'is_published' => false,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListNavigationLinks::class)
            ->callTableColumnAction('is_published', $link);

        $this->assertTrue($link->refresh()->is_published);

        Livewire::actingAs(User::factory()->create())
            ->test(ListNavigationLinks::class)
            ->callTableColumnAction('is_published', $link);

        $this->assertFalse($link->refresh()->is_published);
    }

    public function test_navigation_links_can_be_copied_from_admin_table(): void
    {
        $link = NavigationLink::query()->create([
            'label' => 'Visit',
            'url' => '/visit',
            'location' => 'header',
            'sort_order' => 4,
            'opens_in_new_tab' => false,
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListNavigationLinks::class)
            ->callTableAction('copy', $link)
            ->assertHasNoErrors();

        $this->assertDatabaseCount(NavigationLink::class, 2);
        $this->assertDatabaseHas(NavigationLink::class, [
            'url' => '/visit',
            'location' => 'header',
            'sort_order' => 4,
            'is_published' => true,
        ]);

        $copy = NavigationLink::query()
            ->whereKeyNot($link->id)
            ->firstOrFail();

        $this->assertStringStartsWith('Visit (copy @ ', $copy->label);
    }

    public function test_navigation_copy_keeps_long_labels_within_column_length(): void
    {
        $label = str_repeat('A', 255);

        $link = NavigationLink::query()->create([
            'label' => $label,
            'url' => '/long-label',
            'location' => 'header',
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListNavigationLinks::class)
            ->callTableAction('copy', $link)
            ->assertHasNoErrors();

        $copy = NavigationLink::query()
            ->whereKeyNot($link->id)
            ->firstOrFail();

        $this->assertLessThanOrEqual(255, strlen($copy->label));
        $this->assertStringContainsString('(copy @ ', $copy->label);
    }

    public function test_copying_parent_navigation_link_copies_child_links(): void
    {
        $parent = NavigationLink::query()->create([
            'label' => 'Resources',
            'url' => '/resources',
            'location' => 'header',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'parent_id' => $parent->id,
            'label' => 'Kids',
            'url' => '/resources/kids',
            'location' => 'header',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        NavigationLink::query()->create([
            'parent_id' => $parent->id,
            'label' => 'Students',
            'url' => '/resources/students',
            'location' => 'header',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListNavigationLinks::class)
            ->callTableAction('copy', $parent)
            ->assertHasNoErrors();

        $copy = NavigationLink::query()
            ->whereNull('parent_id')
            ->whereKeyNot($parent->id)
            ->firstOrFail();

        $this->assertStringStartsWith('Resources (copy @ ', $copy->label);
        $this->assertSame(['Kids', 'Students'], $copy->children()->orderBy('sort_order')->pluck('label')->all());
        $this->assertSame(['/resources/kids', '/resources/students'], $copy->children()->orderBy('sort_order')->pluck('url')->all());
    }

    private function documentWithVersion(string $title, string $fileName, string $visibility): FileDocument
    {
        $document = FileDocument::query()->create([
            'title' => $title,
            'file_name' => $fileName,
            'category' => 'Other',
            'is_published' => true,
            'visibility' => $visibility,
        ]);

        $version = FileDocumentVersion::query()->create([
            'file_document_id' => $document->getKey(),
            'disk' => 'public',
            'path' => "file-library/documents/{$fileName}.pdf",
            'original_name' => "{$fileName}.pdf",
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'size' => 1000,
        ]);

        $document->update(['current_version_id' => $version->getKey()]);

        return $document->refresh();
    }
}

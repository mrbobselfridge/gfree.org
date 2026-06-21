<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Pages\Pages\CreatePage;
use App\Filament\Admin\Resources\Pages\Pages\ListPages;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PageRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_redirect_page_sends_visitors_to_local_path_temporarily(): void
    {
        Page::query()->create([
            'title' => 'Old Visit Page',
            'slug' => 'visit-us',
            'is_published' => true,
            'is_redirect' => true,
            'redirect_url' => '/new-here?source=old-link',
            'redirect_status_code' => Page::REDIRECT_TEMPORARY,
        ]);

        $this->get('/visit-us')
            ->assertStatus(Page::REDIRECT_TEMPORARY)
            ->assertRedirect('/new-here?source=old-link');
    }

    public function test_published_redirect_page_can_send_visitors_to_external_url_permanently(): void
    {
        Page::query()->create([
            'title' => 'Register',
            'slug' => 'register',
            'is_published' => true,
            'is_redirect' => true,
            'redirect_url' => 'https://forms.example.com/register',
            'redirect_status_code' => Page::REDIRECT_PERMANENT,
        ]);

        $this->get('/register')
            ->assertStatus(Page::REDIRECT_PERMANENT)
            ->assertRedirect('https://forms.example.com/register');
    }

    public function test_unpublished_redirect_pages_are_not_public(): void
    {
        Page::query()->create([
            'title' => 'Draft Redirect',
            'slug' => 'draft-redirect',
            'is_published' => false,
            'is_redirect' => true,
            'redirect_url' => '/new-here',
        ]);

        $this->get('/draft-redirect')->assertNotFound();
    }

    public function test_redirect_pages_respect_publish_and_expiration_dates(): void
    {
        Page::query()->create([
            'title' => 'Future Redirect',
            'slug' => 'future-redirect',
            'is_published' => true,
            'is_redirect' => true,
            'redirect_url' => '/new-here',
            'publish_at' => now()->addDay(),
        ]);

        Page::query()->create([
            'title' => 'Expired Redirect',
            'slug' => 'expired-redirect',
            'is_published' => true,
            'is_redirect' => true,
            'redirect_url' => '/new-here',
            'expires_at' => now()->subDay(),
        ]);

        $this->get('/future-redirect')->assertNotFound();
        $this->get('/expired-redirect')->assertNotFound();
    }

    public function test_redirect_page_cannot_redirect_to_itself(): void
    {
        $this->expectException(ValidationException::class);

        Page::query()->create([
            'title' => 'Loop',
            'slug' => 'loop',
            'is_published' => true,
            'is_redirect' => true,
            'redirect_url' => '/loop',
        ]);
    }

    public function test_redirect_page_cannot_redirect_to_another_redirect_page(): void
    {
        Page::query()->create([
            'title' => 'First Redirect',
            'slug' => 'first-redirect',
            'is_published' => true,
            'is_redirect' => true,
            'redirect_url' => '/final-page',
        ]);

        $this->expectException(ValidationException::class);

        Page::query()->create([
            'title' => 'Second Redirect',
            'slug' => 'second-redirect',
            'is_published' => true,
            'is_redirect' => true,
            'redirect_url' => '/first-redirect',
        ]);
    }

    public function test_redirect_page_requires_valid_destination(): void
    {
        $this->expectException(ValidationException::class);

        Page::query()->create([
            'title' => 'Bad Redirect',
            'slug' => 'bad-redirect',
            'is_published' => true,
            'is_redirect' => true,
            'redirect_url' => 'not a usable URL',
        ]);
    }

    public function test_redirect_page_can_be_created_from_admin_form(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreatePage::class)
            ->assertSee('Redirect this page')
            ->set('data.is_redirect', true)
            ->set('data.is_published', false)
            ->assertSee('This redirect is saved but will not work publicly until Page is live is enabled.')
            ->set('data.title', 'Give Redirect')
            ->set('data.slug', 'give-now')
            ->set('data.is_published', true)
            ->set('data.redirect_url', '/give')
            ->set('data.redirect_status_code', Page::REDIRECT_PERMANENT)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(Page::class, [
            'title' => 'Give Redirect',
            'slug' => 'give-now',
            'is_redirect' => true,
            'redirect_url' => '/give',
            'redirect_status_code' => Page::REDIRECT_PERMANENT,
        ]);
    }

    public function test_pages_table_identifies_redirect_pages(): void
    {
        Page::query()->create([
            'title' => 'Content Page',
            'slug' => 'content-page',
            'is_published' => true,
        ]);

        Page::query()->create([
            'title' => 'Redirect Page',
            'slug' => 'redirect-page',
            'is_published' => true,
            'is_redirect' => true,
            'redirect_url' => '/content-page',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListPages::class)
            ->assertSee('Redirect Page')
            ->assertSee('Redirect')
            ->assertSee('/content-page');
    }
}

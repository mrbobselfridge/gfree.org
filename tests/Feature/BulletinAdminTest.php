<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Bulletins\Pages\CreateBulletin;
use App\Filament\Admin\Resources\Bulletins\Pages\EditBulletin;
use App\Models\Bulletin;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\OpenAiBulletinExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BulletinAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_bulletins_under_content(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]))
            ->get('/admin/bulletins/create')
            ->assertOk()
            ->assertSee('Bulletin Details')
            ->assertSee('Bulletin PDF')
            ->assertDontSee('PDF Extraction')
            ->assertDontSee('Extracted formatted HTML');

        Livewire::actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]))
            ->test(CreateBulletin::class)
            ->assertFormFieldHidden('extraction_prompt')
            ->assertFormFieldHidden('extracted_html');
    }

    public function test_admin_can_edit_bulletin_pdf_extraction_fields_after_create(): void
    {
        $bulletin = Bulletin::query()->create([
            'title' => 'Sunday Bulletin',
            'bulletin_date' => '2026-06-14',
        ]);

        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]))
            ->get("/admin/bulletins/{$bulletin->getKey()}/edit")
            ->assertOk()
            ->assertSee('PDF Extraction')
            ->assertSee('Extracted formatted HTML');

        Livewire::actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]))
            ->test(EditBulletin::class, ['record' => $bulletin->getKey()])
            ->assertFormFieldVisible('extraction_prompt')
            ->assertFormFieldVisible('extracted_html')
            ->assertSet('data.extraction_prompt', fn (?string $prompt): bool => str_contains(
                (string) $prompt,
                'Extract the important public bulletin content for the church website.',
            ));
    }

    public function test_editor_needs_bulletins_permission(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [],
                'records' => [],
            ],
        ]);

        $this->actingAs($editor)
            ->get('/admin/bulletins')
            ->assertForbidden();

        $editor->update([
            'admin_permissions' => [
                'tools' => [AdminAccess::BULLETINS],
                'records' => [],
            ],
        ]);

        $this->actingAs($editor)
            ->get('/admin/bulletins')
            ->assertOk();
    }

    public function test_bulletin_title_defaults_from_bulletin_date_until_manually_changed(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        Livewire::actingAs($admin)
            ->test(CreateBulletin::class)
            ->set('data.bulletin_date', '2026-06-14')
            ->assertSet('data.title', 'Bulletin June 14, 2026')
            ->set('data.bulletin_date', '2026-06-21')
            ->assertSet('data.title', 'Bulletin June 21, 2026')
            ->set('data.title', 'Graduation Sunday Bulletin')
            ->set('data.bulletin_date', '2026-06-28')
            ->assertSet('data.title', 'Graduation Sunday Bulletin');
    }

    public function test_openai_extractor_sends_pdf_and_prompt(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('bulletins/pdfs/test.pdf', '%PDF-1.4 test bulletin');

        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'openai_api_key' => 'test-key',
            'openai_bulletin_model' => 'gpt-4o-mini',
        ]);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output_text' => '<h2>Sunday Bulletin</h2><p>Welcome.</p>',
            ]),
        ]);

        $bulletin = Bulletin::query()->create([
            'title' => 'Sunday Bulletin',
            'pdf_path' => 'bulletins/pdfs/test.pdf',
            'extraction_prompt' => 'Only extract announcements.',
        ]);

        $html = app(OpenAiBulletinExtractor::class)->extract($bulletin);

        $this->assertSame('<h2>Sunday Bulletin</h2><p>Welcome.</p>', $html);

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();
            $content = data_get($payload, 'input.0.content');

            return $request->url() === 'https://api.openai.com/v1/responses'
                && data_get($payload, 'model') === 'gpt-4o-mini'
                && data_get($content, '0.type') === 'input_file'
                && data_get($content, '0.filename') === 'test.pdf'
                && str_starts_with(data_get($content, '0.file_data'), 'data:application/pdf;base64,')
                && data_get($content, '1.type') === 'input_text'
                && str_contains(data_get($content, '1.text'), 'Only extract announcements.');
        });
    }

    public function test_openai_extractor_uses_site_settings_prompt_when_bulletin_has_no_override(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('bulletins/pdfs/test.pdf', '%PDF-1.4 test bulletin');

        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'openai_api_key' => 'test-key',
            'openai_bulletin_model' => 'gpt-4o-mini',
            'ai_bulletin_extraction_prompt' => 'Use the church-wide bulletin prompt.',
        ]);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output_text' => '<h2>Sunday Bulletin</h2><p>Welcome.</p>',
            ]),
        ]);

        $bulletin = Bulletin::query()->create([
            'title' => 'Sunday Bulletin',
            'pdf_path' => 'bulletins/pdfs/test.pdf',
            'extraction_prompt' => null,
        ]);

        $html = app(OpenAiBulletinExtractor::class)->extract($bulletin);

        $this->assertSame('<h2>Sunday Bulletin</h2><p>Welcome.</p>', $html);

        Http::assertSent(function (Request $request): bool {
            return str_contains(
                (string) data_get($request->data(), 'input.0.content.1.text'),
                'Use the church-wide bulletin prompt.',
            );
        });
    }

    public function test_extract_pdf_action_updates_rich_text_field(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('bulletins/pdfs/test.pdf', '%PDF-1.4 test bulletin');

        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'openai_api_key' => 'test-key',
            'openai_bulletin_model' => 'gpt-4o-mini',
        ]);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output_text' => '<h2>Extracted Bulletin</h2>',
            ]),
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $bulletin = Bulletin::query()->create([
            'title' => 'Sunday Bulletin',
            'bulletin_date' => '2026-05-31',
            'pdf_path' => 'bulletins/pdfs/test.pdf',
            'extraction_prompt' => 'Extract everything.',
        ]);

        Livewire::actingAs($admin)
            ->test(EditBulletin::class, ['record' => $bulletin->getKey()])
            ->callAction('extractPdf')
            ->assertHasNoActionErrors();

        $this->assertSame('<h2>Extracted Bulletin</h2>', $bulletin->refresh()->extracted_html);
    }
}

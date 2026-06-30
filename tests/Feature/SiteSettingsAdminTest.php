<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\SiteSettings\Pages\EditSiteSetting;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\OpenAiUsageSummary;
use App\Support\SiteDesignPalette;
use Filament\Schemas\Components\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SiteSettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_settings_edit_page_has_sections_and_cancel_actions(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get("/admin/site-settings/{$settings->getKey()}/edit")
            ->assertOk()
            ->assertSee('Organizational Information')
            ->assertSee('Contact Name')
            ->assertSee('Contact Email')
            ->assertSee('Contact Phone')
            ->assertSee('Contact Notes')
            ->assertSee('Site variables')
            ->assertSee('Variable')
            ->assertDontSee('Sunday service times')
            ->assertDontSee('Office hours')
            ->assertSee('Site logo')
            ->assertSee('Default page header image')
            ->assertSee('Site design and customization')
            ->assertDontSee('Site design</span>', false)
            ->assertSee('Site accent color')
            ->assertSee('Accent text color')
            ->assertSee('Soft accent color')
            ->assertSee('Background colors')
            ->assertSee('Custom CSS')
            ->assertSee('Header custom JS')
            ->assertSee('Body top custom JS')
            ->assertSee('Body bottom custom JS')
            ->assertSee('Dashboard notes')
            ->assertSee('AI Settings')
            ->assertSee('OpenAI API key')
            ->assertSee('OpenAI API key ID')
            ->assertSee('Preferred AI model')
            ->assertSee('OpenAI Admin API key')
            ->assertSee('OpenAI usage spend')
            ->assertSee('AI content prompt')
            ->assertSee('Social and Additional Links')
            ->assertSee('TikTok URL')
            ->assertSee('LinkedIn URL')
            ->assertSee('Google Business Profile URL')
            ->assertSee('Pinterest URL')
            ->assertSee('X URL')
            ->assertSee('Threads URL')
            ->assertSee('Additional links')
            ->assertDontSee('Social and Video URLs')
            ->assertDontSee('One Church URL')
            ->assertSee('Google tracking')
            ->assertSee('Google Tag Manager container ID')
            ->assertSee('Google Analytics measurement ID')
            ->assertDontSee('Ministries Settings')
            ->assertDontSee('Sermons Settings')
            ->assertDontSee('Leaders Settings')
            ->assertDontSee('Announcements Settings')
            ->assertDontSee('Bulletins Settings')
            ->assertDontSee('Can also be managed in the Ministries area.')
            ->assertDontSee('Can also be managed in the Sermons area.')
            ->assertDontSee('Can also be managed in the Leaders area.')
            ->assertDontSee('Can also be managed in the Announcements area.')
            ->assertDontSee('Can also be managed in the Bulletins area.')
            ->assertDontSee('Sermons YouTube feed URL')
            ->assertSee('Collapse all')
            ->assertSee('Expand all')
            ->assertSee('Save');

        $this->assertGreaterThanOrEqual(2, substr_count($response->getContent(), 'Cancel'));

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->assertSchemaComponentExists('site-settings-section-controls')
            ->assertSchemaComponentExists(
                'site-settings-organizational-information',
                checkComponentUsing: fn (Section $component): bool => $component->isCollapsible()
                    && $component->isCollapsed()
                    && $component->shouldPersistCollapsed(),
            )
            ->assertSchemaComponentExists(
                'site-settings-ai-settings',
                checkComponentUsing: fn (Section $component): bool => $component->isCollapsible()
                    && $component->isCollapsed()
                    && $component->shouldPersistCollapsed(),
            )
            ->assertSchemaComponentExists(
                'site-settings-site-variables',
                checkComponentUsing: fn (Section $component): bool => $component->isCollapsible()
                    && $component->isCollapsed()
                    && $component->shouldPersistCollapsed(),
            )
            ->assertSchemaComponentExists(
                'site-settings-site-design-elements',
                checkComponentUsing: fn (Section $component): bool => $component->isCollapsible()
                    && $component->isCollapsed()
                    && $component->shouldPersistCollapsed(),
            )
            ->assertSchemaComponentExists(
                'site-settings-dashboard-notes',
                checkComponentUsing: fn (Section $component): bool => $component->isCollapsible()
                    && $component->isCollapsed()
                    && $component->shouldPersistCollapsed(),
            )
            ->assertSchemaComponentExists(
                'site-settings-social-and-additional-links',
                checkComponentUsing: fn (Section $component): bool => $component->isCollapsible()
                    && $component->isCollapsed()
                    && $component->shouldPersistCollapsed(),
            )
            ->assertSchemaComponentDoesNotExist('site-settings-ministries-settings');
    }

    public function test_site_settings_contact_fields_can_be_saved(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.contact_name', 'Jane Admin')
            ->set('data.contact_email', 'jane@example.com')
            ->set('data.contact_phone', '555-0100')
            ->set('data.contact_notes', 'Primary internal contact for user questions.')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'id' => $settings->getKey(),
            'contact_name' => 'Jane Admin',
            'contact_email' => 'jane@example.com',
            'contact_phone' => '555-0100',
            'contact_notes' => 'Primary internal contact for user questions.',
        ]);
    }

    public function test_site_variables_can_be_saved_with_html_values(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.site_variables', [
                [
                    'name' => 'Address',
                    'variable' => 'address',
                    'value' => '<p>305 Keystone <strong>Hill</strong></p>',
                ],
                [
                    'name' => 'Service Times',
                    'variable' => 'service-times',
                    'value' => '<p>9:15 &amp; 11 AM</p>',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([
            [
                'name' => 'Address',
                'variable' => 'address',
                'value' => '<p>305 Keystone <strong>Hill</strong></p>',
            ],
            [
                'name' => 'Service Times',
                'variable' => 'service-times',
                'value' => '<p>9:15 &amp; 11 AM</p>',
            ],
        ], $settings->refresh()->site_variables);
    }

    public function test_site_variables_reject_invalid_or_duplicate_variable_names(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.site_variables', [
                [
                    'name' => 'Bad Variable',
                    'variable' => 'Bad Variable!',
                    'value' => 'Bad',
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['site_variables.0.variable']);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.site_variables', [
                [
                    'name' => 'Address',
                    'variable' => 'address',
                    'value' => 'First',
                ],
                [
                    'name' => 'Address Again',
                    'variable' => 'address',
                    'value' => 'Second',
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['site_variables']);
    }

    public function test_site_settings_editor_without_code_access_can_view_but_not_change_site_variables(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'site_variables' => [
                [
                    'name' => 'Address',
                    'variable' => 'address',
                    'value' => '<p>Existing address</p>',
                ],
            ],
        ]);

        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [AdminAccess::SITE_SETTINGS],
                'records' => [],
            ],
        ]);

        $this->actingAs($editor)
            ->get("/admin/site-settings/{$settings->getKey()}/edit")
            ->assertOk()
            ->assertSee('Site Variables')
            ->assertSee('Existing address')
            ->assertDontSee('Custom CSS');

        Livewire::actingAs($editor)
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.site_variables', [
                [
                    'name' => 'Address',
                    'variable' => 'address',
                    'value' => '<p>Changed address</p>',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('<p>Existing address</p>', $settings->refresh()->site_variables[0]['value']);
    }

    public function test_site_variables_migration_backfills_address_and_service_times(): void
    {
        Schema::table('site_settings', function ($table): void {
            if (! Schema::hasColumn('site_settings', 'address')) {
                $table->text('address')->nullable();
            }

            if (! Schema::hasColumn('site_settings', 'sunday_service_times')) {
                $table->text('sunday_service_times')->nullable();
            }

            if (! Schema::hasColumn('site_settings', 'office_hours')) {
                $table->text('office_hours')->nullable();
            }
        });

        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'site_variables' => null,
        ]);

        DB::table('site_settings')
            ->where('id', $settings->getKey())
            ->update([
                'address' => '<p>305 Keystone Hill Road</p>',
                'sunday_service_times' => '<p>9:15 &amp; 11 AM</p>',
                'office_hours' => '<p>Not backfilled</p>',
            ]);

        $migration = require database_path('migrations/2026_06_20_000004_add_site_variables_to_site_settings_table.php');
        $migration->up();

        $this->assertSame([
            [
                'name' => 'Address',
                'variable' => 'address',
                'value' => '<p>305 Keystone Hill Road</p>',
            ],
            [
                'name' => 'Service Times',
                'variable' => 'service-times',
                'value' => '<p>9:15 &amp; 11 AM</p>',
            ],
        ], SiteSetting::query()->findOrFail($settings->getKey())->site_variables);

        $this->assertFalse(Schema::hasColumn('site_settings', 'address'));
        $this->assertFalse(Schema::hasColumn('site_settings', 'sunday_service_times'));
        $this->assertFalse(Schema::hasColumn('site_settings', 'office_hours'));
    }

    public function test_site_settings_dashboard_notes_can_be_saved(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.dashboard_notes', '<p><a href="/admin/pages">Review pages</a> before Sunday.</p>')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'id' => $settings->getKey(),
            'dashboard_notes' => '<p><a href="/admin/pages">Review pages</a> before Sunday.</p>',
        ]);
    }

    public function test_site_settings_ai_content_prompt_can_be_saved(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.ai_content_prompt', 'Rewrite this for local church visitors.')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'id' => $settings->getKey(),
            'ai_content_prompt' => 'Rewrite this for local church visitors.',
        ]);
    }

    public function test_default_page_header_image_can_be_saved(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.default_page_header_image_path', ['site-settings/page-header-images/default-banner.jpg'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'id' => $settings->getKey(),
            'default_page_header_image_path' => 'site-settings/page-header-images/default-banner.jpg',
        ]);
    }

    public function test_site_settings_openai_fields_can_be_saved(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.openai_api_key', 'test-openai-key')
            ->set('data.openai_api_key_id', 'key_app_123')
            ->set('data.openai_content_model', 'gpt-5-mini')
            ->set('data.openai_admin_api_key', 'test-admin-key')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'id' => $settings->getKey(),
            'openai_api_key' => 'test-openai-key',
            'openai_api_key_id' => 'key_app_123',
            'openai_content_model' => 'gpt-5-mini',
            'openai_admin_api_key' => 'test-admin-key',
        ]);
    }

    public function test_openai_usage_summary_uses_admin_costs_api(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'openai_api_key_id' => 'key_app_123',
            'openai_admin_api_key' => 'test-admin-key',
        ]);

        Http::fake([
            'https://api.openai.com/v1/organization/costs*' => Http::response([
                'data' => [
                    [
                        'results' => [
                            [
                                'api_key_id' => 'key_app_123',
                                'amount' => [
                                    'value' => 4.25,
                                    'currency' => 'usd',
                                ],
                            ],
                            [
                                'api_key_id' => 'key_other_456',
                                'amount' => [
                                    'value' => 20.00,
                                    'currency' => 'usd',
                                ],
                            ],
                        ],
                    ],
                    [
                        'results' => [
                            [
                                'api_key_id' => 'key_app_123',
                                'amount' => [
                                    'value' => 1.75,
                                    'currency' => 'usd',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $summary = app(OpenAiUsageSummary::class)->currentMonth();

        $this->assertSame('ok', $summary['status']);
        $this->assertStringContainsString('$6.00', $summary['body']);

        Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api.openai.com/v1/organization/costs')
            && $request->hasHeader('Authorization', 'Bearer test-admin-key')
            && str_contains($request->url(), 'bucket_width=1d')
            && str_contains($request->url(), 'group_by%5B0%5D=api_key_id'));
    }

    public function test_site_settings_url_fields_accept_external_urls_and_local_paths(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.facebook_url', 'http://facebook.example/twyxtco')
            ->set('data.instagram_url', '/instagram')
            ->set('data.youtube_url', '/sermons')
            ->set('data.tiktok_url', 'https://tiktok.example/twyxtco')
            ->set('data.linkedin_url', 'https://linkedin.example/company/twyxtco')
            ->set('data.google_business_profile_url', 'https://business.google.com/example')
            ->set('data.pinterest_url', 'https://pinterest.example/twyxtco')
            ->set('data.x_url', 'https://x.example/twyxtco')
            ->set('data.threads_url', 'https://threads.example/@twyxtco')
            ->set('data.social_link_placements.facebook_url', SiteSetting::SOCIAL_LINK_PLACEMENT_UTILITY)
            ->set('data.social_link_placements.instagram_url', SiteSetting::SOCIAL_LINK_PLACEMENT_FOOTER)
            ->set('data.additional_social_links', [
                [
                    'label' => 'Podcast',
                    'url' => '/podcast',
                    'placement' => SiteSetting::SOCIAL_LINK_PLACEMENT_UTILITY,
                    'image_path' => 'site-settings/additional-links/podcast.png',
                ],
                [
                    'label' => 'Newsletter',
                    'url' => 'https://newsletter.example/twyxtco',
                    'placement' => SiteSetting::SOCIAL_LINK_PLACEMENT_BOTH,
                    'image_path' => ['site-settings/additional-links/newsletter.png'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'id' => $settings->getKey(),
            'facebook_url' => 'http://facebook.example/twyxtco',
            'instagram_url' => '/instagram',
            'youtube_url' => '/sermons',
            'tiktok_url' => 'https://tiktok.example/twyxtco',
            'linkedin_url' => 'https://linkedin.example/company/twyxtco',
            'google_business_profile_url' => 'https://business.google.com/example',
            'pinterest_url' => 'https://pinterest.example/twyxtco',
            'x_url' => 'https://x.example/twyxtco',
            'threads_url' => 'https://threads.example/@twyxtco',
        ]);

        $settings->refresh();

        $this->assertSame(
            SiteSetting::SOCIAL_LINK_PLACEMENT_UTILITY,
            $settings->social_link_placements['facebook_url'] ?? null,
        );
        $this->assertSame(
            SiteSetting::SOCIAL_LINK_PLACEMENT_FOOTER,
            $settings->social_link_placements['instagram_url'] ?? null,
        );

        $this->assertSame([
            [
                'label' => 'Podcast',
                'url' => '/podcast',
                'placement' => SiteSetting::SOCIAL_LINK_PLACEMENT_UTILITY,
                'image_path' => 'site-settings/additional-links/podcast.png',
            ],
            [
                'label' => 'Newsletter',
                'url' => 'https://newsletter.example/twyxtco',
                'placement' => SiteSetting::SOCIAL_LINK_PLACEMENT_BOTH,
                'image_path' => 'site-settings/additional-links/newsletter.png',
            ],
        ], $settings->additional_social_links);
    }

    public function test_site_design_background_colors_can_be_saved(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.design_background_colors', [
                [
                    'key' => 'white',
                    'name' => 'White',
                    'hex' => '#ffffff',
                ],
                [
                    'name' => 'Midnight Blue',
                    'hex' => '102030',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $colors = $settings->refresh()->backgroundColors();

        $this->assertSame([
            ['key' => 'white', 'name' => 'White', 'hex' => '#ffffff'],
            ['key' => 'midnight-blue', 'name' => 'Midnight Blue', 'hex' => '#102030'],
        ], $colors);
        $this->assertSame('Midnight Blue', SiteDesignPalette::normalizeBackgroundColors($colors)[1]['name']);
    }

    public function test_site_design_public_colors_css_and_custom_scripts_can_be_saved(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.design_accent_color', '445566')
            ->set('data.design_accent_text_color', '#223344')
            ->set('data.design_accent_soft_color', '#ddeeff')
            ->set('data.custom_css', ".page-hero h1 {\n    text-transform: uppercase;\n}")
            ->set('data.header_custom_js', '<script>window.headerCustom = true;</script>')
            ->set('data.body_top_custom_js', '<script>window.bodyTopCustom = true;</script>')
            ->set('data.body_bottom_custom_js', '<script>window.bodyBottomCustom = true;</script>')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'id' => $settings->getKey(),
            'design_accent_color' => '#445566',
            'design_accent_text_color' => '#223344',
            'design_accent_soft_color' => '#ddeeff',
            'custom_css' => ".page-hero h1 {\n    text-transform: uppercase;\n}",
            'header_custom_js' => '<script>window.headerCustom = true;</script>',
            'body_top_custom_js' => '<script>window.bodyTopCustom = true;</script>',
            'body_bottom_custom_js' => '<script>window.bodyBottomCustom = true;</script>',
        ]);
    }

    public function test_site_settings_editor_without_code_access_cannot_change_custom_css_or_scripts(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
            'custom_css' => '.site-header { color: red; }',
            'header_custom_js' => '<script>window.headerCustom = "old";</script>',
            'body_top_custom_js' => '<script>window.bodyTopCustom = "old";</script>',
            'body_bottom_custom_js' => '<script>window.bodyBottomCustom = "old";</script>',
        ]);

        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'admin_permissions' => [
                'tools' => [AdminAccess::SITE_SETTINGS],
                'records' => [],
            ],
        ]);

        $this->actingAs($editor)
            ->get("/admin/site-settings/{$settings->getKey()}/edit")
            ->assertOk()
            ->assertDontSee('Custom CSS')
            ->assertDontSee('Header custom JS')
            ->assertDontSee('Body top custom JS')
            ->assertDontSee('Body bottom custom JS');

        Livewire::actingAs($editor)
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.custom_css', '.site-header { color: blue; }')
            ->set('data.header_custom_js', '<script>window.headerCustom = "new";</script>')
            ->set('data.body_top_custom_js', '<script>window.bodyTopCustom = "new";</script>')
            ->set('data.body_bottom_custom_js', '<script>window.bodyBottomCustom = "new";</script>')
            ->call('save')
            ->assertHasNoFormErrors();

        $settings->refresh();

        $this->assertSame('.site-header { color: red; }', $settings->custom_css);
        $this->assertSame('<script>window.headerCustom = "old";</script>', $settings->header_custom_js);
        $this->assertSame('<script>window.bodyTopCustom = "old";</script>', $settings->body_top_custom_js);
        $this->assertSame('<script>window.bodyBottomCustom = "old";</script>', $settings->body_bottom_custom_js);
    }

    public function test_site_settings_url_fields_reject_non_url_text(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.linkedin_url', 'linkedin page')
            ->set('data.additional_social_links', [
                [
                    'label' => 'Podcast',
                    'url' => 'podcast page',
                    'image_path' => 'site-settings/additional-links/podcast.png',
                ],
            ])
            ->call('save')
            ->assertHasFormErrors([
                'linkedin_url',
                'additional_social_links.0.url',
            ]);
    }

    public function test_site_settings_google_tracking_fields_can_be_saved(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.google_tag_manager_id', 'gtm-church1')
            ->set('data.google_analytics_measurement_id', 'g-churchga1')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(SiteSetting::class, [
            'id' => $settings->getKey(),
            'google_tag_manager_id' => 'GTM-CHURCH1',
            'google_analytics_measurement_id' => 'G-CHURCHGA1',
        ]);
    }

    public function test_site_settings_google_tracking_fields_reject_invalid_ids(): void
    {
        $settings = SiteSetting::query()->create([
            'church_name' => 'TwyxtCo Church',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditSiteSetting::class, ['record' => $settings->getKey()])
            ->set('data.google_tag_manager_id', 'G-CHURCHGA1')
            ->set('data.google_analytics_measurement_id', 'GTM-CHURCH1')
            ->call('save')
            ->assertHasFormErrors([
                'google_tag_manager_id',
                'google_analytics_measurement_id',
            ]);
    }
}

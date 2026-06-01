<?php

namespace Tests\Feature;

use App\Filament\Admin\Forms\RichContentPlugins\AiContentRewritePlugin;
use App\Filament\Admin\Forms\RichEditorDefaults;
use App\Models\SiteSetting;
use App\Support\OpenAiContentRewriter;
use Filament\Forms\Components\RichEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiContentRewriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_openai_content_rewriter_sends_prompt_and_rich_text_html(): void
    {
        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'openai_api_key' => 'test-key',
            'openai_bulletin_model' => 'gpt-5-mini',
        ]);

        config([
            'services.openai.content_model' => 'gpt-5-mini',
        ]);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output_text' => '<h2>Welcome</h2><ul><li>Join us Sunday.</li></ul>',
            ]),
        ]);

        $html = app(OpenAiContentRewriter::class)->rewrite(
            html: '<p>Come to church.</p>',
            prompt: 'Rewrite for first-time visitors.',
        );

        $this->assertSame('<h2>Welcome</h2><ul><li>Join us Sunday.</li></ul>', $html);

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();
            $text = data_get($payload, 'input.0.content.0.text');

            return $request->url() === 'https://api.openai.com/v1/responses'
                && data_get($payload, 'model') === 'gpt-5-mini'
                && data_get($payload, 'input.0.content.0.type') === 'input_text'
                && str_contains($text, 'Rewrite for first-time visitors.')
                && str_contains($text, '<p>Come to church.</p>')
                && str_contains($text, 'Return only clean semantic HTML');
        });
    }

    public function test_rich_editor_defaults_include_ai_rewrite_tool(): void
    {
        $editor = RichEditorDefaults::configure(RichEditor::make('body'));
        $plugins = (fn (): array => $this->plugins)->call($editor);
        $toolbarButtons = (fn (): array => $this->toolbarButtons)->call($editor);
        $aiPlugins = array_filter(
            $plugins,
            fn (object $plugin): bool => $plugin instanceof AiContentRewritePlugin,
        );

        $this->assertNotEmpty($aiPlugins);
        $this->assertContainsOnlyInstancesOf(AiContentRewritePlugin::class, $aiPlugins);
        $this->assertContains('aiRewrite', collect($toolbarButtons)->flatten()->all());

        $plugin = new AiContentRewritePlugin;

        $this->assertSame('aiRewrite', $plugin->getEditorTools()[0]->getName());
        $this->assertSame('aiRewrite', $plugin->getEditorActions()[0]->getName());
    }
}
